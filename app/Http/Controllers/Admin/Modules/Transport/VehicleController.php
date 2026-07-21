<?php

namespace App\Http\Controllers\Admin\Modules\Transport;

use App\Modules\Transport\Models\TransportVehicle;
use App\Modules\Transport\Services\TransportVehicleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(private readonly TransportVehicleService $vehicles) {}

    public function index(): View
    {
        $vehicles = TransportVehicle::where('school_id', app('current_school_id'))->orderBy('registration_no')->get();

        return view('admin.modules.transport.vehicles.index', compact('vehicles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->vehicles->make(app('current_school_id'), $this->validated($request));

        return back()->with('status', __('Vehicle added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $vehicle = TransportVehicle::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->vehicles->modify($vehicle, $this->validated($request));

        return back()->with('status', __('Vehicle updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'registration_no' => ['required', 'string', 'max:30'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [], ['registration_no' => 'registration number']);
    }
}
