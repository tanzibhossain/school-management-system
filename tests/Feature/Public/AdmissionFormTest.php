<?php

namespace Tests\Feature\Public;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Public online-admission form (admission_form block) — full field set, age
 * check, duplicate protection, configurable hidden fields, photo upload.
 */
class AdmissionFormTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SchoolClass $class;

    private AcademicYear $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        SiteSetting::create(['school_id' => $this->school->id, 'site_name' => 'Test School']);
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class Six', 'min_age' => 9, 'max_age' => 15]);
        $this->year = AcademicYear::create(['school_id' => $this->school->id, 'year' => 2026, 'is_current' => true]);
        $this->publishAdmissionPage();
    }

    private function publishAdmissionPage(array $blockData = []): void
    {
        $page = Page::updateOrCreate(
            ['school_id' => $this->school->id, 'slug' => 'online-admission'],
            ['title' => 'Online Admission', 'status' => 'published'],
        );
        PageLayout::where('page_id', $page->id)->delete();
        PageLayout::create([
            'school_id' => $this->school->id, 'page_id' => $page->id, 'is_published' => true, 'published_at' => now(),
            'layout_json' => ['template' => 'full', 'blocks' => [['type' => 'admission_form', 'data' => $blockData]]],
        ]);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Little', 'last_name' => 'Rahim',
            'dob' => now()->subYears(11)->format('Y-m-d'),
            'birth_certificate_no' => 'BC-123', 'gender' => 'male', 'religion' => 'Islam',
            'desired_class_id' => $this->class->id, 'desired_academic_year_id' => $this->year->id,
            'previous_school' => 'Old School', 'gpa' => '4.50',
            'father_name' => 'Karim', 'father_phone' => '01700000000', 'father_nid' => 'NID-111',
            'mother_name' => 'Fatima', 'mother_nid' => 'NID-222',
            'guardian_type' => 'father', 'present_address' => '123 School Road',
        ], $overrides);
    }

    public function test_form_renders_all_sections(): void
    {
        $this->get('/online-admission')->assertOk()
            ->assertSee('Birth Certificate No.')
            ->assertSee('Parent Information')
            ->assertSee('Present Address')
            ->assertSee('Class Six');
    }

    public function test_valid_submission_stores_core_and_form_data(): void
    {
        $this->from('/online-admission')->post('/admission', $this->validData())
            ->assertRedirect('/online-admission')->assertSessionHas('admission_reference');

        $app = AdmissionApplication::first();
        $this->assertSame('Little Rahim', $app->applicant_name);
        $this->assertSame('BC-123', $app->birth_certificate_no);
        $this->assertSame('submitted', $app->status);
        $this->assertSame('Islam', $app->form_data['religion']);
        $this->assertSame('4.50', $app->form_data['gpa']);
        $this->assertSame('Karim', $app->form_data['father_name']);
    }

    public function test_age_is_validated_against_the_class_range(): void
    {
        // Class Six is configured min 9 / max 15.
        $this->from('/online-admission')->post('/admission', $this->validData(['dob' => now()->subYears(7)->format('Y-m-d')]))
            ->assertSessionHasErrors('dob');
        $this->from('/online-admission')->post('/admission', $this->validData(['dob' => now()->subYears(17)->format('Y-m-d')]))
            ->assertSessionHasErrors('dob');
        $this->assertDatabaseCount('admission_applications', 0);
    }

    public function test_class_without_age_range_accepts_any_age(): void
    {
        $open = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Play Group']); // no min/max

        $this->from('/online-admission')->post('/admission', $this->validData([
            'desired_class_id' => $open->id, 'dob' => now()->subYears(5)->format('Y-m-d'),
            'birth_certificate_no' => 'BC-777', 'father_nid' => 'NID-777', 'father_phone' => '01777777777',
        ]))->assertSessionHasNoErrors()->assertRedirect('/online-admission');

        $this->assertDatabaseHas('admission_applications', ['birth_certificate_no' => 'BC-777']);
    }

    public function test_duplicate_is_rejected(): void
    {
        $this->post('/admission', $this->validData());
        $this->assertDatabaseCount('admission_applications', 1);

        // Same birth certificate → rejected.
        $this->from('/online-admission')->post('/admission', $this->validData(['father_nid' => 'NID-999', 'father_phone' => '01999999999']))
            ->assertSessionHasErrors('duplicate');
        $this->assertDatabaseCount('admission_applications', 1);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->from('/online-admission')->post('/admission', [])
            ->assertSessionHasErrors(['first_name', 'dob', 'birth_certificate_no', 'gender', 'religion',
                'desired_class_id', 'previous_school', 'gpa', 'father_name', 'father_nid', 'mother_name', 'mother_nid', 'present_address']);
    }

    public function test_hidden_fields_are_not_rendered(): void
    {
        $this->publishAdmissionPage(['hidden' => 'blood_group, student_phone']);
        $this->get('/online-admission')->assertOk()
            ->assertDontSee('name="blood_group"', false)
            ->assertDontSee('name="student_phone"', false)
            ->assertSee('name="birth_certificate_no"', false); // required field still there
    }

    public function test_photo_upload_is_stored(): void
    {
        Storage::fake('public');
        $this->post('/admission', $this->validData(['photo' => UploadedFile::fake()->image('p.jpg', 300, 300)]));

        $app = AdmissionApplication::first();
        $this->assertNotNull($app->form_data['photo']);
        Storage::disk('public')->assertExists($app->form_data['photo']);
    }
}
