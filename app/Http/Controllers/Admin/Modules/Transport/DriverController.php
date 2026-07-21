<?php

namespace App\Http\Controllers\Admin\Modules\Transport;

use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Services\TransportDriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function __construct(private readonly TransportDriverService $drivers) {}

    public function index(): View
    {
        $drivers = TransportDriver::where('school_id', app('current_school_id'))->orderBy('name')->get();

        return view('admin.modules.transport.drivers.index', compact('drivers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->drivers->make(app('current_school_id'), $this->validated($request));

        return back()->with('status', 'Driver added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $driver = TransportDriver::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->drivers->modify($driver, $this->validated($request));

        return back()->with('status', 'Driver updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,on_leave,inactive'],
        ], [], ['license_no' => 'license number']);
    }
}
