<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — People area (students, staff, designations/departments, users).
 */
class PeopleAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create([
            'name'                  => 'Test School',
            'is_active'             => true,
            'currency'              => 'BDT',
            'timezone'              => 'Asia/Dhaka',
            'locale'                => 'en',
            'academic_year_pattern' => 'jan_dec',
        ]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/students')->assertRedirect('/login');
    }

    public function test_admin_can_open_people_screens(): void
    {
        $this->actingAs($this->admin);

        foreach (['/admin/students', '/admin/students/create', '/admin/staff', '/admin/designations', '/admin/departments', '/admin/users'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    // ── Designations / departments ──────────────────────────────────────────────

    public function test_can_create_designation_and_block_delete_when_in_use(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/designations', ['name' => 'Senior Teacher'])->assertRedirect();
        $designation = Designation::where('school_id', $this->school->id)->firstOrFail();
        $this->assertDatabaseHas('designations', ['id' => $designation->id, 'name' => 'Senior Teacher']);

        Staff::create(['school_id' => $this->school->id, 'name' => 'T1', 'gender' => 'male', 'designation_id' => $designation->id, 'employee_id' => 'EMP/1']);

        $this->delete("/admin/designations/{$designation->id}")->assertRedirect();
        $this->assertDatabaseHas('designations', ['id' => $designation->id]); // not deleted
    }

    // ── Staff ───────────────────────────────────────────────────────────────────

    public function test_can_hire_and_deactivate_staff(): void
    {
        $this->actingAs($this->admin);
        $dep = Department::create(['school_id' => $this->school->id, 'name' => 'Science']);

        $this->post('/admin/staff', [
            'name' => 'Jane Teacher', 'gender' => 'female', 'department_id' => $dep->id,
            'joining_date' => '2026-01-05', 'employment_type' => 'full_time', 'basic_salary' => 40000,
        ])->assertRedirect();

        $staff = Staff::where('school_id', $this->school->id)->where('name', 'Jane Teacher')->firstOrFail();
        $this->assertNotNull($staff->employee_id);

        $this->patch("/admin/staff/{$staff->id}/deactivate")->assertRedirect();
        $this->assertDatabaseMissing('staff', ['id' => $staff->id, 'status' => 'active']);
    }

    // ── Students ────────────────────────────────────────────────────────────────

    public function test_can_enrol_and_deactivate_student(): void
    {
        $this->actingAs($this->admin);

        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);

        $this->post('/admin/students', [
            'name' => 'Little Learner', 'gender' => 'male', 'admission_number' => 'ADM-001',
            'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id,
            'roll_number' => '1',
            'guardian_name' => 'Parent One', 'guardian_relation' => 'father', 'guardian_phone' => '01700000000',
        ])->assertRedirect(route('admin.students.index'));

        $student = Student::where('school_id', $this->school->id)->where('admission_number', 'ADM-001')->firstOrFail();
        $this->assertDatabaseHas('student_academics', ['student_id' => $student->id, 'class_id' => $class->id, 'section_id' => $section->id, 'is_current' => true]);
        $this->assertDatabaseHas('student_guardians', ['student_id' => $student->id, 'name' => 'Parent One', 'is_primary' => true]);

        $this->patch("/admin/students/{$student->id}/deactivate")->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'inactive']);
    }

    public function test_enrol_requires_gender_and_admission_number(): void
    {
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $section = Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);

        $this->post('/admin/students', [
            'name' => 'No Gender', 'academic_year_id' => $year->id, 'class_id' => $class->id, 'section_id' => $section->id,
        ])->assertSessionHasErrors(['gender', 'admission_number']);
    }

    // ── Users & roles ───────────────────────────────────────────────────────────

    public function test_can_create_change_role_and_deactivate_user(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/users', [
            'name' => 'Reception One', 'email' => 'recep@school.test', 'role' => 'receptionist',
            'password' => 'Secret@1234', 'password_confirmation' => 'Secret@1234',
        ])->assertRedirect();

        $user = User::where('email', 'recep@school.test')->firstOrFail();
        $this->assertTrue($user->hasRole('receptionist'));

        $this->patch("/admin/users/{$user->id}/role", ['role' => 'teacher'])->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('teacher'));

        $this->patch("/admin/users/{$user->id}/deactivate")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_cannot_deactivate_self(): void
    {
        $this->actingAs($this->admin);
        $this->patch("/admin/users/{$this->admin->id}/deactivate")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'is_active' => true]);
    }

    public function test_duplicate_user_email_is_rejected(): void
    {
        $this->actingAs($this->admin);
        User::factory()->create(['school_id' => $this->school->id, 'email' => 'dupe@school.test']);

        $this->post('/admin/users', [
            'name' => 'Dupe', 'email' => 'dupe@school.test', 'role' => 'teacher',
            'password' => 'Secret@1234', 'password_confirmation' => 'Secret@1234',
        ])->assertSessionHasErrors('email');
    }
}
