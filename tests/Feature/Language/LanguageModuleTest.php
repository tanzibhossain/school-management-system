<?php

namespace Tests\Feature\Language;

use App\Models\User;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Models\Translation;
use App\Modules\School\Models\School;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Language module — locale switching, DB-backed translations, and the admin
 * Languages/Translations screens. English is the source (English-as-key).
 */
class LanguageModuleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(LanguageSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_switch_route_stores_the_locale_and_middleware_applies_it(): void
    {
        $this->get('/language/bn')->assertRedirect();
        $this->assertSame('bn', session('app_locale'));

        // Any subsequent web request runs under the chosen locale.
        $this->get('/login');
        $this->assertSame('bn', app()->getLocale());
    }

    public function test_switch_rejects_an_inactive_language(): void
    {
        $this->get('/language/xx')->assertRedirect();
        $this->assertNull(session('app_locale'));
    }

    public function test_db_translation_is_served_for_the_active_locale(): void
    {
        Translation::create(['locale' => 'bn', 'key' => 'Notices', 'value' => 'নোটিশ']);

        $this->withSession(['app_locale' => 'bn'])->get('/login');

        $this->assertSame('নোটিশ', __('Notices'));
        $this->assertSame('Untranslated stays English', __('Untranslated stays English'));
    }

    public function test_admin_languages_screen_loads_and_language_can_be_added(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/languages')->assertOk();

        $this->post('/admin/languages', [
            'code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'is_rtl' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('languages', ['code' => 'ar', 'is_rtl' => true, 'is_active' => true]);
    }

    public function test_translations_editor_saves_values(): void
    {
        $row = Translation::create(['locale' => 'bn', 'key' => 'Dashboard', 'value' => null]);

        $this->actingAs($this->admin);
        $this->get('/admin/languages/bn/translations')->assertOk()->assertSee('Dashboard');

        $this->put('/admin/languages/bn/translations', ['t' => [$row->id => 'ড্যাশবোর্ড']])->assertRedirect();
        $this->assertSame('ড্যাশবোর্ড', $row->fresh()->value);
    }

    public function test_scan_registers_codebase_strings_for_non_english_locales(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/languages/scan')->assertRedirect();

        // Thousands of __() strings exist post-extraction; bn gets a row per key.
        $this->assertGreaterThan(500, Translation::where('locale', 'bn')->count());
        // English is the source — never registered for editing.
        $this->assertSame(0, Translation::where('locale', 'en')->count());
    }

    public function test_translation_seeder_ships_bangla_pack_without_overwriting_edits(): void
    {
        // A hand-edited value must survive re-seeding.
        Translation::create(['locale' => 'bn', 'key' => 'Cancel', 'value' => 'কাস্টম']);

        $this->seed(\Database\Seeders\TranslationSeeder::class);

        $this->assertSame('কাস্টম', Translation::where('locale', 'bn')->where('key', 'Cancel')->first()->value);
        $this->assertSame('ড্যাশবোর্ড', Translation::where('locale', 'bn')->where('key', 'Dashboard')->first()->value);
        $this->assertGreaterThan(200, Translation::where('locale', 'bn')->whereNotNull('value')->count());
    }

    public function test_default_language_cannot_be_deactivated_or_deleted(): void
    {
        $en = Language::where('code', 'en')->first();

        $this->actingAs($this->admin);
        $this->put("/admin/languages/{$en->id}", ['is_active' => 0])->assertRedirect();
        $this->assertTrue($en->fresh()->is_active);

        $this->delete("/admin/languages/{$en->id}")->assertRedirect();
        $this->assertDatabaseHas('languages', ['code' => 'en']);
    }
}
