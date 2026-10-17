<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Blade admin — Online admission review (approve → enrol, reject), and the
 * role-gated route that serves applicants' private uploads.
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
        $this->seed(RoleSeeder::class);

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

    private function applicationWithUpload(string $path, string $ref = 'ADM-REF-1'): AdmissionApplication
    {
        $app = $this->application($ref);
        $app->update(['form_data' => ['custom' => [
            'custom_1000' => ['label' => 'Emergency Contact', 'type' => 'text', 'value' => '01811111111'],
            'custom_1001' => ['label' => 'Birth Certificate Scan', 'type' => 'file', 'value' => $path],
        ]]]);

        return $app;
    }

    public function test_review_page_shows_the_full_application(): void
    {
        $this->actingAs($this->admin);
        $app = $this->application();
        $app->update(['birth_certificate_no' => 'BC-123', 'notes' => 'Needs a front seat', 'form_data' => [
            'religion' => 'Islam', 'previous_school' => 'Old School', 'father_name' => 'Karim',
            'present_address' => '123 School Road', 'photo' => 'admissions/1/p.jpg',
            'custom' => [
                'custom_1000' => ['label' => 'Emergency Contact', 'type' => 'text', 'value' => '01811111111'],
                'custom_1001' => ['label' => 'Birth Certificate Scan', 'type' => 'file', 'value' => 'admissions/1/bc.jpg'],
                'custom_1002' => ['label' => 'Has A Sibling Here', 'type' => 'checkbox', 'value' => true],
            ],
        ]]);

        $this->get("/admin/admissions/{$app->id}")->assertOk()
            ->assertSee('BC-123')->assertSee('Islam')->assertSee('Old School')->assertSee('Karim')
            ->assertSee('123 School Road')->assertSee('Needs a front seat')
            ->assertSee('Emergency Contact')->assertSee('01811111111')->assertSee('Has A Sibling Here')
            ->assertSee(route('admin.admissions.file', [$app->id, 'photo']), false)
            ->assertSee(route('admin.admissions.file', [$app->id, 'custom_1001']), false);
    }

    public function test_review_page_still_loads_without_form_data(): void
    {
        $this->actingAs($this->admin);
        $app = $this->application(); // API-style application: no form_data at all

        $this->get("/admin/admissions/{$app->id}")->assertOk()->assertDontSee('Additional Information');
    }

    public function test_admin_can_open_an_applicants_private_upload(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('admissions/1/bc.jpg', 'jpeg-bytes');
        $app = $this->applicationWithUpload('admissions/1/bc.jpg');

        $response = $this->actingAs($this->admin)->get(route('admin.admissions.file', [$app->id, 'custom_1001']));

        $response->assertOk();
        $this->assertSame('jpeg-bytes', $response->streamedContent());
    }

    public function test_photos_stored_before_uploads_became_private_still_open(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('admissions/old.jpg', 'old-bytes');
        $app = $this->application();
        $app->update(['form_data' => ['photo' => 'admissions/old.jpg']]);

        $response = $this->actingAs($this->admin)->get(route('admin.admissions.file', [$app->id, 'photo']));

        $response->assertOk();
        $this->assertSame('old-bytes', $response->streamedContent());
    }

    public function test_guests_cannot_open_applicant_uploads(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('admissions/1/bc.jpg', 'jpeg-bytes');
        $app = $this->applicationWithUpload('admissions/1/bc.jpg');

        $this->get(route('admin.admissions.file', [$app->id, 'custom_1001']))->assertRedirect();
    }

    public function test_only_admins_can_open_applicant_uploads(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('admissions/1/bc.jpg', 'jpeg-bytes');
        $app = $this->applicationWithUpload('admissions/1/bc.jpg');

        foreach (['accountant', 'teacher', 'receptionist', 'librarian', 'student', 'parent'] as $role) {
            $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
            $user->assignRole($role);

            $this->actingAs($user)->get(route('admin.admissions.file', [$app->id, 'custom_1001']))->assertForbidden();
        }
    }

    public function test_upload_route_only_serves_files_recorded_on_that_application(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('admissions/1/bc.jpg', 'jpeg-bytes');
        $app = $this->applicationWithUpload('admissions/1/bc.jpg');
        $this->actingAs($this->admin);

        $this->get(route('admin.admissions.file', [$app->id, 'custom_1000']))->assertNotFound(); // a text answer, not a file
        $this->get(route('admin.admissions.file', [$app->id, 'custom_9999']))->assertNotFound(); // no such field
        $this->get(route('admin.admissions.file', [$app->id, 'photo']))->assertNotFound();       // no photo uploaded
        $this->get("/admin/admissions/{$app->id}/files/..%2F..%2F.env")->assertNotFound();      // not a field key

        // Another school's application is invisible, even with a real file behind it.
        $other = School::create(['name' => 'Other School', 'is_active' => true]);
        $foreign = AdmissionApplication::create([
            'school_id' => $other->id, 'reference_number' => 'ADM-REF-X', 'status' => 'submitted',
            'applicant_name' => 'Other Kid', 'gender' => 'female',
            'desired_class_id' => $this->class->id, 'desired_academic_year_id' => $this->year->id,
            'guardian_name' => 'Parent', 'guardian_phone' => '01900000000', 'guardian_relation' => 'mother',
            'form_data' => ['custom' => ['custom_1001' => ['label' => 'Scan', 'type' => 'file', 'value' => 'admissions/1/bc.jpg']]],
        ]);
        $this->get(route('admin.admissions.file', [$foreign->id, 'custom_1001']))->assertNotFound();
    }
}
