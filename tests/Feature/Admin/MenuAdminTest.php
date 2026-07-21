<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\Menu;
use App\Modules\Website\Models\Page;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade navigation-menu editor — saves the drag-built tree via MenuService.
 */
class MenuAdminTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_menu_editor_screen_loads(): void
    {
        $this->actingAs($this->admin)->get('/admin/menus')->assertOk();
    }

    public function test_admin_can_save_a_nested_menu_tree(): void
    {
        $home = Page::create(['school_id' => $this->school->id, 'slug' => 'home', 'title' => 'Home', 'status' => 'published', 'is_homepage' => true]);

        $items = json_encode([
            ['label' => 'Home', 'type' => 'page', 'page_id' => $home->id, 'target' => '_self'],
            ['label' => 'About', 'type' => 'dropdown', 'target' => '_self', 'children' => [
                ['label' => 'History', 'type' => 'external', 'url' => '/history', 'target' => '_blank'],
            ]],
        ]);

        $this->actingAs($this->admin)->put('/admin/menus', ['items' => $items])->assertRedirect();

        $menu = Menu::forSchool($this->school->id)->with('items.children')->first();
        $this->assertNotNull($menu);
        $this->assertCount(2, $menu->items); // two top-level items

        $about = $menu->items->firstWhere('label', 'About');
        $this->assertSame('dropdown', $about->type);
        $this->assertCount(1, $about->children);
        $this->assertSame('History', $about->children->first()->label);
        $this->assertSame('_blank', $about->children->first()->target);
    }

    public function test_invalid_page_reference_is_dropped_on_save(): void
    {
        // A page_id that doesn't belong to the school must not be persisted.
        $items = json_encode([
            ['label' => 'Bad', 'type' => 'page', 'page_id' => 99999, 'target' => '_self'],
        ]);

        $this->actingAs($this->admin)->put('/admin/menus', ['items' => $items])->assertRedirect();

        $menu = Menu::forSchool($this->school->id)->with('items')->first();
        $this->assertNull($menu->items->first()->page_id);
    }
}
