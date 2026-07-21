<?php

namespace App\Http\Controllers\Admin\Hr;

use App\Modules\Leave\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    public function index(): View
    {
        $types = LeaveType::where('school_id', app('current_school_id'))->orderBy('name')->get();

        return view('admin.hr.leave-types.index', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        LeaveType::create($this->validated($request) + ['school_id' => app('current_school_id')]);

        return back()->with('status', 'Leave type added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $type = LeaveType::where('school_id', app('current_school_id'))->findOrFail($id);
        $type->update($this->validated($request));

        return back()->with('status', 'Leave type updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $type = LeaveType::where('school_id', app('current_school_id'))->findOrFail($id);
        $type->update(['is_active' => false]);

        return back()->with('status', 'Leave type deactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'applies_to' => ['required', 'in:student,staff,both'],
            'max_days_per_year' => ['nullable', 'integer', 'min:0', 'max:365'],
            'requires_attachment' => ['nullable', 'boolean'],
            'is_paid' => ['nullable', 'boolean'],
        ], [], ['applies_to' => 'applies to', 'max_days_per_year' => 'max days per year']);

        $data['requires_attachment'] = $request->boolean('requires_attachment');
        $data['is_paid'] = $request->boolean('is_paid');
        $data['is_active'] = true;

        return $data;
    }
}
