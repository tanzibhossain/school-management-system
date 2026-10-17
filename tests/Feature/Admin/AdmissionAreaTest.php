<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentDocument;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
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

    /**
     * A website-form application with a photo and a custom file upload on the
     * private disk, as submitPublicForm() leaves it.
     *
     * @param  array<string, mixed>  $overrides  application columns
     * @param  array<string, mixed>  $formData  merged into form_data
     */
    private function websiteApplication(array $overrides = [], array $formData = []): AdmissionApplication
    {
        $sid = $this->school->id;
        Storage::disk('local')->put("admissions/{$sid}/photo.jpg", 'photo-bytes');
        Storage::disk('local')->put("admissions/{$sid}/scan.jpg", 'scan-bytes');

        $app = $this->application();
        $app->update(array_merge([
            'guardian_name' => 'Uncle Bob', 'guardian_relation' => 'other', 'guardian_phone' => '01900000000',
            'form_data' => array_merge([
                'religion' => 'Islam',
                'father_name' => 'Karim', 'father_phone' => '01711111111',
                'mother_name' => 'Fatima',
                'present_address' => '123 School Road', 'permanent_address' => null, 'is_permanent_same' => true,
                'photo' => "admissions/{$sid}/photo.jpg",
                'custom' => [
                    'custom_1000' => ['label' => 'Emergency Contact', 'type' => 'text', 'value' => '01811111111'],
                    'custom_1001' => ['label' => 'Birth Certificate Scan', 'type' => 'file', 'value' => "admissions/{$sid}/scan.jpg"],
                ],
            ], $formData),
        ], $overrides));

        return $app;
    }

    private function approve(AdmissionApplication $app): TestResponse
    {
        return $this->actingAs($this->admin)->patch("/admin/admissions/{$app->id}/approve", [
            'admission_number' => 'ADM-200', 'section_id' => $this->section->id,
        ]);
    }

    public function test_approval_carries_the_application_details_into_the_student(): void
    {
        Storage::fake('local');
        Storage::fake('minio');
        $app = $this->websiteApplication();

        $this->approve($app)->assertSessionHasNoErrors()->assertRedirect(route('admin.admissions.index'));

        $student = Student::where('admission_number', 'ADM-200')->with(['guardians', 'addresses', 'documents'])->firstOrFail();
        $this->assertSame('Islam', $student->religion);

        // Photo copied to student storage; the application keeps its original.
        $this->assertNotNull($student->photo);
        $this->assertSame('photo-bytes', Storage::disk('minio')->get($student->photo));
        Storage::disk('local')->assertExists("admissions/{$this->school->id}/photo.jpg");

        $guardians = $student->guardians->keyBy('relation');
        $this->assertCount(3, $guardians);
        $this->assertTrue((bool) $guardians['other']->is_primary);
        $this->assertSame('Uncle Bob', $guardians['other']->name);
        $this->assertSame('Karim', $guardians['father']->name);
        $this->assertSame('01711111111', $guardians['father']->phone);
        $this->assertFalse((bool) $guardians['father']->is_primary);
        $this->assertSame('Fatima', $guardians['mother']->name);

        $addresses = $student->addresses->keyBy('type');
        $this->assertSame('123 School Road', $addresses['present']->address);
        $this->assertSame('123 School Road', $addresses['permanent']->address); // "same as present"

        $this->assertCount(1, $student->documents);
        $document = $student->documents->first();
        $this->assertSame('other', $document->document_type);
        $this->assertSame('Birth Certificate Scan.jpg', $document->original_name);
        $this->assertSame('scan-bytes', Storage::disk('minio')->get($document->file_path));
    }

    public function test_a_parent_who_is_the_primary_guardian_is_not_added_twice(): void
    {
        Storage::fake('local');
        Storage::fake('minio');
        $app = $this->websiteApplication(['guardian_name' => 'Karim', 'guardian_relation' => 'father', 'guardian_phone' => '01711111111']);

        $this->approve($app)->assertSessionHasNoErrors();

        $student = Student::where('admission_number', 'ADM-200')->firstOrFail();
        $this->assertSame(['father', 'mother'], $student->guardians()->orderBy('relation')->pluck('relation')->all());
        $this->assertSame(1, $student->guardians()->where('relation', 'father')->count());
        $this->assertTrue((bool) $student->guardians()->where('relation', 'father')->value('is_primary'));
    }

    public function test_a_failed_approval_leaves_no_student_rows_or_copied_files(): void
    {
        Storage::fake('local');
        Storage::fake('minio');
        $this->section->update(['capacity' => 0]); // full → enrol() refuses
        $app = $this->websiteApplication();

        $this->approve($app)->assertSessionHas('error');

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_guardians', 0);
        $this->assertDatabaseCount('student_addresses', 0);
        $this->assertDatabaseCount('student_documents', 0);
        $this->assertSame([], Storage::disk('minio')->allFiles());
        $this->assertSame('submitted', $app->fresh()->status);
    }

    public function test_an_api_application_without_form_data_still_approves(): void
    {
        Storage::fake('minio');
        $app = $this->application();

        $this->approve($app)->assertSessionHasNoErrors();

        $student = Student::where('admission_number', 'ADM-200')->firstOrFail();
        $this->assertNull($student->photo);
        $this->assertSame(1, $student->guardians()->count());
        $this->assertTrue((bool) $student->guardians()->value('is_primary'));
        $this->assertSame(0, StudentDocument::where('student_id', $student->id)->count());
    }

    public function test_review_page_links_to_the_student_after_approval(): void
    {
        Storage::fake('minio');
        $app = $this->application();
        $this->approve($app);
        $student = Student::where('admission_number', 'ADM-200')->firstOrFail();

        $this->get("/admin/admissions/{$app->id}")->assertOk()
            ->assertSee(route('admin.students.show', $student->id), false);
        $this->get("/admin/students/{$student->id}")->assertOk()
            ->assertSee($app->reference_number);
    }
}
