<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Online admission review (approve → enrol, reject).
 */
class AdmissionAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private SchoolClass $class;

    private Section $section;

    private AcademicYear $year;

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

        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);
    }

    private function application(string $ref = 'ADM-REF-1'): AdmissionApplication
    {
        return AdmissionApplication::create([
            'school_id' => $this->school->id, 'reference_number' => $ref, 'status' => 'submitted',
            'applicant_name' => 'New Kid', 'gender' => 'male',
            'desired_class_id' => $this->class->id, 'desired_academic_year_id' => $this->year->id,
            'guardian_name' => 'Parent', 'guardian_phone' => '01700000000', 'guardian_relation' => 'father',
        ]);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        $app = $this->application();
        $this->get('/admin/admissions')->assertOk();
        $this->get("/admin/admissions/{$app->id}")->assertOk()->assertSee('New Kid');
    }

    public function test_approve_enrols_student(): void
    {
        $this->actingAs($this->admin);
        $app = $this->application();

        $this->patch("/admin/admissions/{$app->id}/approve", [
            'admission_number' => 'ADM-100', 'section_id' => $this->section->id, 'roll_number' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.admissions.index'));

        $student = Student::where('school_id', $this->school->id)->where('admission_number', 'ADM-100')->firstOrFail();
        $this->assertDatabaseHas('admission_applications', ['id' => $app->id, 'status' => 'approved', 'created_student_id' => $student->id]);
        $this->assertDatabaseHas('student_academics', ['student_id' => $student->id, 'class_id' => $this->class->id, 'section_id' => $this->section->id, 'is_current' => true]);
    }

    public function test_reject_application(): void
    {
        $this->actingAs($this->admin);
        $app = $this->application();

        $this->patch("/admin/admissions/{$app->id}/reject", ['reason' => 'Incomplete documents'])->assertRedirect();
        $this->assertDatabaseHas('admission_applications', ['id' => $app->id, 'status' => 'rejected']);
    }

    public function test_approve_requires_admission_number_and_section(): void
    {
        $this->actingAs($this->admin);
        $app = $this->application();

        $this->patch("/admin/admissions/{$app->id}/approve", [])->assertSessionHasErrors(['admission_number', 'section_id']);
    }
}
