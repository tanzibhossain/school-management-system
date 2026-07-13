<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — HR › Leave (types CRUD + student/staff request approvals).
 */
class LeaveAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    private function type(string $appliesTo = 'both'): LeaveType
    {
        return LeaveType::create([
            'school_id' => $this->school->id, 'name' => 'Casual', 'applies_to' => $appliesTo,
            'is_active' => true, 'requires_attachment' => false, 'is_paid' => true,
        ]);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        foreach (['/admin/leave-types', '/admin/student-leave', '/admin/staff-leave'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_leave_type_crud(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/leave-types', ['name' => 'Sick', 'applies_to' => 'both', 'is_paid' => 1])->assertRedirect();
        $type = LeaveType::where('school_id', $this->school->id)->firstOrFail();
        $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'name' => 'Sick']);

        $this->put("/admin/leave-types/{$type->id}", ['name' => 'Sick Leave', 'applies_to' => 'staff'])->assertRedirect();
        $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'name' => 'Sick Leave', 'applies_to' => 'staff']);

        $this->delete("/admin/leave-types/{$type->id}")->assertRedirect();
        $this->assertDatabaseHas('leave_types', ['id' => $type->id, 'is_active' => false]);
    }

    public function test_staff_leave_approve_and_reject(): void
    {
        $this->actingAs($this->admin);
        $type = $this->type('staff');
        $staff = Staff::create(['school_id' => $this->school->id, 'name' => 'T', 'gender' => 'male', 'employee_id' => 'EMP/1', 'status' => 'active']);

        $make = fn () => StaffLeaveRequest::create([
            'school_id' => $this->school->id, 'staff_id' => $staff->id, 'leave_type_id' => $type->id,
            'from_date' => '2026-06-01', 'to_date' => '2026-06-02', 'working_days' => 2,
            'reason' => 'Personal', 'status' => 'pending', 'requested_by' => $this->admin->id,
        ]);

        $r1 = $make();
        $this->patch("/admin/staff-leave/{$r1->id}/approve")->assertRedirect();
        $this->assertDatabaseHas('staff_leave_requests', ['id' => $r1->id, 'status' => 'approved']);

        $r2 = $make();
        $this->patch("/admin/staff-leave/{$r2->id}/reject", ['reason' => 'Insufficient notice'])->assertRedirect();
        $this->assertDatabaseHas('staff_leave_requests', ['id' => $r2->id, 'status' => 'rejected']);
    }

    public function test_cannot_approve_already_decided_request(): void
    {
        $this->actingAs($this->admin);
        $type = $this->type('staff');
        $staff = Staff::create(['school_id' => $this->school->id, 'name' => 'T', 'gender' => 'male', 'employee_id' => 'EMP/2', 'status' => 'active']);
        $r = StaffLeaveRequest::create([
            'school_id' => $this->school->id, 'staff_id' => $staff->id, 'leave_type_id' => $type->id,
            'from_date' => '2026-06-01', 'to_date' => '2026-06-02', 'working_days' => 2,
            'reason' => 'x', 'status' => 'approved', 'requested_by' => $this->admin->id,
        ]);

        $this->patch("/admin/staff-leave/{$r->id}/approve")->assertSessionHasErrors();
    }

    public function test_student_leave_reject(): void
    {
        $this->actingAs($this->admin);
        $type = $this->type('student');
        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'S', 'gender' => 'male', 'admission_number' => 'ADM-1', 'status' => 'active']);

        $r = StudentLeaveRequest::create([
            'school_id' => $this->school->id, 'student_id' => $student->id, 'class_id' => $class->id,
            'section_id' => $section->id, 'academic_year_id' => $year->id, 'leave_type_id' => $type->id,
            'from_date' => '2026-06-01', 'to_date' => '2026-06-02', 'working_days' => 2,
            'reason' => 'Family event', 'status' => 'pending', 'requested_by' => $this->admin->id,
        ]);

        $this->patch("/admin/student-leave/{$r->id}/reject", ['reason' => 'No documents'])->assertRedirect();
        $this->assertDatabaseHas('student_leave_requests', ['id' => $r->id, 'status' => 'rejected']);
    }
}
