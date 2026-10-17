<?php

namespace Tests\Feature\Public;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Language\Models\Translation;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\PageRenderService;
use Database\Seeders\LanguageSeeder;
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
            'school_id' => $this->school->id, 'page_id' => $page->id, 'locale' => 'en',
            'is_published' => true, 'published_at' => now(),
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

    /**
     * Regression test for a real production bug: PageRenderService used to
     * embed Closures (show/getLabel/isRequired field-visibility helpers)
     * directly inside the admission_form block's rendered data.
     * PageRenderService::renderPage() caches that entire structure via
     * CacheTags::remember() — any real store this app ships with (Redis, or
     * database/file on shared cPanel hosting; phpunit.xml pins
     * CACHE_STORE=array for the test suite, which holds plain PHP values in
     * memory and never actually serializes anything, so this class of bug
     * was completely invisible to every other test here) cannot serialize a
     * Closure and throws "Serialization of 'Closure' is not allowed" on
     * every real, non-preview page load. Calling serialize() directly
     * reproduces that exact failure without needing a real cache backend.
     */
    public function test_rendered_block_data_is_serializable_for_the_cache(): void
    {
        $page = Page::where('school_id', $this->school->id)->where('slug', 'online-admission')->firstOrFail();
        $layout = $page->publishedLayout->first();

        $view = app(PageRenderService::class)->buildView($this->school->id, $layout->layout_json, 'en');

        $this->assertIsString(serialize($view));
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

    /**
     * Reported: "online admission form still not fully translated" — Last
     * name, Blood group, Student phone, Student photo, Permanent address,
     * Notes. Root cause was upstream of the __()-wrapped fallback labels in
     * admission_form.blade.php: PageRenderService::normalizeAdmissionFields()
     * always baked in a hardcoded English default label (e.g. 'Last name')
     * even when the admin never customized the field, so
     * $standard[$key]['label'] was never null and the Blade layer's own
     * $getLabel($key, __('Last name')) fallback never actually ran. Fixed by
     * leaving 'label' null there when the admin hasn't set one, letting the
     * Blade default win.
     */
    public function test_standard_field_labels_translate_under_bn_when_admin_hasnt_customized_them(): void
    {
        $this->seed(LanguageSeeder::class); // en (default) + bn, both active
        foreach ([
            'Last name' => 'শেষ নাম',
            'Blood group' => 'রক্তের গ্রুপ',
            'Student phone' => 'শিক্ষার্থীর ফোন',
            'Student photo' => 'শিক্ষার্থীর ছবি',
            'Permanent address' => 'স্থায়ী ঠিকানা',
            'Notes' => 'নোট',
        ] as $key => $value) {
            Translation::create(['locale' => 'bn', 'key' => $key, 'value' => $value]);
        }

        $this->withSession(['app_locale' => 'bn'])->get('/online-admission')
            ->assertOk()
            ->assertSee('শেষ নাম')
            ->assertSee('রক্তের গ্রুপ')
            ->assertSee('শিক্ষার্থীর ফোন')
            ->assertSee('শিক্ষার্থীর ছবি')
            ->assertSee('স্থায়ী ঠিকানা')
            ->assertSee('নোট');
    }

    /** An admin-typed custom label is data, not a UI string -- it must never be swapped for the __() default. */
    public function test_an_admin_customized_field_label_is_never_overridden_by_the_translated_default(): void
    {
        $this->seed(LanguageSeeder::class);
        Translation::create(['locale' => 'bn', 'key' => 'Last name', 'value' => 'শেষ নাম']);
        $this->publishAdmissionPage(['fields' => ['last_name' => ['label' => 'Surname']]]);

        $this->withSession(['app_locale' => 'bn'])->get('/online-admission')
            ->assertOk()
            ->assertSee('Surname')
            ->assertDontSee('শেষ নাম');
    }

    public function test_custom_select_field_renders_its_options(): void
    {
        $this->publishAdmissionPage(['fields' => [
            'transport_need' => ['label' => 'Transport Need', 'type' => 'select', 'options' => 'School Bus, Private', 'enabled' => '1'],
        ]]);

        $this->get('/online-admission')->assertOk()
            ->assertSee('name="custom[transport_need]"', false)
            ->assertSee('<option value="School Bus"', false)
            ->assertSee('<option value="Private"', false);
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
