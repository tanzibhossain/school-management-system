<?php

namespace App\Http\Controllers\Admin\Modules\Payroll;

use App\Modules\Payroll\Services\StaffSalaryValueService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class StaffSalaryController extends Controller
{
    public function __construct(private readonly StaffSalaryValueService $salaries) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $staff = Staff::where('school_id', $schoolId)->where('is_trash', false)
            ->orderBy('name')->get(['id', 'name', 'employee_id']);

        $summary = $staff->mapWithKeys(fn ($s) => [$s->id => $this->salaries->calculateGrossAndNet($schoolId, $s->id)]);

        return view('admin.modules.payroll.staff-salaries.index', compact('staff', 'summary'));
    }

    public function edit(int $staffId): View
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($staffId);
        $breakdown = $this->salaries->breakdown($schoolId, $staffId);

        return view('admin.modules.payroll.staff-salaries.edit', compact('staff', 'breakdown'));
    }

    public function update(Request $request, int $staffId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        Staff::where('school_id', $schoolId)->findOrFail($staffId);

        $request->validate([
            'amounts' => ['array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $values = [];
        foreach ((array) $request->input('amounts', []) as $componentId => $amount) {
            $values[] = ['component_id' => (int) $componentId, 'amount' => $amount === null || $amount === '' ? 0 : $amount];
        }

        $this->salaries->setValues($schoolId, $staffId, $values);

        return redirect()->route('admin.payroll.staff-salaries.index')->with('status', 'Salary structure saved.');
    }
}
