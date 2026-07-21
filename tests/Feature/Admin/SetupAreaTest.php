<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — Setup area (session/web auth, reuses module Services).
 */
class SetupAreaTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School',
            'is_active' => true,
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'academic_year_pattern' => 'jan_dec',
        ]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    // ── Auth gate ──────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/classes')->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_setup_screens(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            '/admin/academic-years', '/admin/classes', '/admin/subjects',
            '/admin/groups', '/admin/versions', '/admin/shifts',
            '/admin/school', '/admin/modules',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    // ── Academic years ──────────────────────────────────────────────────────────

    public function test_can_create_and_set_current_academic_year(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/academic-years', ['year' => '2026'])->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['school_id' => $this->school->id, 'year' => '2026']);

        $year = AcademicYear::where('school_id', $this->school->id)->where('year', '2026')->first();
        $this->post("/admin/academic-years/{$year->id}/set-current")->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $year->id, 'is_current' => true]);
    }

    public function test_cannot_delete_the_current_year(): void
    {
        $this->actingAs($this->admin);
        $year = AcademicYear::create(['school_id' => $this->school->id, 'year' => '2026', 'is_current' => true]);

        $this->delete("/admin/academic-years/{$year->id}")->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $year->id, 'is_trash' => false]);
    }

    // ── Classes + sections ──────────────────────────────────────────────────────

    public function test_can_create_class_and_nested_section(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/classes', ['name' => 'Class 6'])->assertRedirect();
        $class = SchoolClass::where('school_id', $this->school->id)->where('name', 'Class 6')->firstOrFail();

        $this->post("/admin/classes/{$class->id}/sections", ['name' => 'A', 'capacity' => 40])->assertRedirect();
        $this->assertDatabaseHas('sections', [
            'school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A', 'capacity' => 40,
        ]);
    }

    public function test_cannot_delete_class_with_sections(): void
    {
        $this->actingAs($this->admin);
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        Section::create(['school_id' => $this->school->id, 'class_id' => $class->id, 'name' => 'A']);

        $this->delete("/admin/classes/{$class->id}")->assertRedirect();
        $this->assertDatabaseHas('classes', ['id' => $class->id, 'is_trash' => false]);
    }

    public function test_duplicate_class_name_is_rejected(): void
    {
        $this->actingAs($this->admin);
        SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);

        $this->post('/admin/classes', ['name' => 'Class 6'])->assertSessionHasErrors('name');
    }

    // ── Subjects + reference lists ──────────────────────────────────────────────

    public function test_can_create_subject_and_reference_items(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/subjects', ['name' => 'Mathematics', 'sub_code' => 'MATH'])->assertRedirect();
        $this->assertDatabaseHas('subjects', ['school_id' => $this->school->id, 'name' => 'Mathematics']);

        $this->post('/admin/groups', ['name' => 'Science'])->assertRedirect();
        $this->assertDatabaseHas('groups', ['school_id' => $this->school->id, 'name' => 'Science']);

        $this->post('/admin/shifts', ['name' => 'Morning'])->assertRedirect();
        $this->assertDatabaseHas('shifts', ['school_id' => $this->school->id, 'name' => 'Morning']);
    }

    public function test_reference_unknown_type_is_404(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/nonsense')->assertNotFound();
    }

    // ── School settings + module toggles ────────────────────────────────────────

    public function test_can_update_school_settings_and_phones(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/school', [
            'name' => 'Renamed School',
            'currency' => 'usd',
            'country_code' => 'bd',
            'established' => '1942',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'academic_year_pattern' => 'jul_jun',
            'phones' => [['phone' => '01700000000', 'show_in_header' => '1']],
            'primary_phone' => 0,
            // School codes (three label/value pairs)
            'institution_code_label' => 'EIIN',
            'institution_code' => '115394',
            'school_code_label' => 'School code',
            'school_code' => '5556',
            // Appearance / SEO (merged into School settings)
            'primary_color' => '#123456',
            'meta_title' => 'Welcome to Renamed School',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('schools', [
            'id' => $this->school->id, 'name' => 'Renamed School', 'currency' => 'USD', 'country_code' => 'BD',
            'institution_code' => '115394', 'school_code_label' => 'School code', 'school_code' => '5556',
        ]);
        $this->assertDatabaseHas('school_phones', ['school_id' => $this->school->id, 'phone' => '01700000000', 'is_primary' => true, 'show_in_header' => true]);
        $this->assertDatabaseHas('site_settings', ['school_id' => $this->school->id, 'primary_color' => '#123456', 'meta_title' => 'Welcome to Renamed School']);
        $this->assertSame('1942', $this->school->fresh()->established->format('Y'));
    }

    public function test_can_toggle_optional_modules(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/modules', ['enabled' => ['library', 'transport']])->assertRedirect();

        $this->assertDatabaseHas('school_module_settings', ['school_id' => $this->school->id, 'module' => 'library', 'is_enabled' => true]);
        $this->assertDatabaseHas('school_module_settings', ['school_id' => $this->school->id, 'module' => 'messaging', 'is_enabled' => false]);
    }
}
