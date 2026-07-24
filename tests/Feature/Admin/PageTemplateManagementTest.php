<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\PageTemplate;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin › Website page templates management screen (rename/delete). See
 * docs/modules/28-elementor-block-editor-plan.md §7s.
 */
class PageTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

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
    }

    public function test_index_lists_only_this_schools_own_templates(): void
    {
        $this->actingAs($this->admin);

        $mine = PageTemplate::create([
            'school_id' => $this->school->id, 'name' => 'My Template', 'layout_json' => '{"blocks":[]}',
        ]);
        PageTemplate::create([
            'school_id' => null, 'name' => 'Global Starter', 'layout_json' => '{"blocks":[]}',
        ]);

        $response = $this->get('/admin/page-templates');

        $response->assertOk();
        $response->assertSee('My Template');
        $response->assertDontSee('Global Starter');
    }

    public function test_update_renames_a_template(): void
    {
        $this->actingAs($this->admin);

        $template = PageTemplate::create([
            'school_id' => $this->school->id, 'name' => 'Old Name', 'layout_json' => '{"blocks":[]}',
        ]);

        $response = $this->put("/admin/page-templates/{$template->id}", ['name' => 'New Name']);

        $response->assertRedirect(route('admin.page-templates.index'));
        $this->assertDatabaseHas('page_templates', ['id' => $template->id, 'name' => 'New Name']);
    }

    public function test_destroy_deletes_a_template_without_touching_pages(): void
    {
        $this->actingAs($this->admin);

        $template = PageTemplate::create([
            'school_id' => $this->school->id, 'name' => 'Disposable', 'layout_json' => '{"blocks":[]}',
        ]);

        $response = $this->delete("/admin/page-templates/{$template->id}");

        $response->assertRedirect(route('admin.page-templates.index'));
        $this->assertDatabaseMissing('page_templates', ['id' => $template->id]);
    }

    public function test_a_school_cannot_rename_or_delete_another_schools_template(): void
    {
        $this->actingAs($this->admin);

        $otherSchool = School::create([
            'name' => 'Other School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $theirs = PageTemplate::create([
            'school_id' => $otherSchool->id, 'name' => 'Not Yours', 'layout_json' => '{"blocks":[]}',
        ]);

        $this->put("/admin/page-templates/{$theirs->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->delete("/admin/page-templates/{$theirs->id}")->assertNotFound();
        $this->assertDatabaseHas('page_templates', ['id' => $theirs->id, 'name' => 'Not Yours']);
    }

    public function test_global_templates_cannot_be_renamed_or_deleted_from_this_screen(): void
    {
        $this->actingAs($this->admin);

        $global = PageTemplate::create([
            'school_id' => null, 'name' => 'Global Starter', 'layout_json' => '{"blocks":[]}',
        ]);

        $this->put("/admin/page-templates/{$global->id}", ['name' => 'Hijacked'])->assertNotFound();
        $this->delete("/admin/page-templates/{$global->id}")->assertNotFound();
        $this->assertDatabaseHas('page_templates', ['id' => $global->id, 'name' => 'Global Starter']);
    }
}
