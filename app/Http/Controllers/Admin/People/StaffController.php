<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\Academic\Models\Subject;
use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(private readonly StaffService $staff) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $staff = Staff::where('school_id', $schoolId)
            ->where('is_trash', false)
            ->with(['designation:id,name', 'department:id,name', 'subject:id,name'])
            ->orderBy('name')
            ->get();

        return view('admin.people.staff.index', [
            'staff' => $staff,
            'designations' => Designation::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, app('current_school_id'));

        $this->staff->hire(app('current_school_id'), $data);

        return back()->with('status', 'Staff member hired.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($id);
        $staff->update($this->validated($request, $schoolId));

        return back()->with('status', 'Staff member updated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($id);
        $this->staff->terminate($staff);

        return back()->with('status', 'Staff member deactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $schoolId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation_id' => ['nullable', 'integer', "exists:designations,id,school_id,{$schoolId}"],
            'department_id' => ['nullable', 'integer', "exists:departments,id,school_id,{$schoolId}"],
            'subject_id' => ['nullable', 'integer', "exists:subjects,id,school_id,{$schoolId}"],
            'gender' => ['nullable', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'rfid_number' => ['nullable', 'string', 'max:50'],
        ], [], [
            'designation_id' => 'designation',
            'department_id' => 'department',
            'basic_salary' => 'basic salary',
        ]);
    }
}
