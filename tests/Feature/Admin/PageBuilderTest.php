<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin › Website page builder — create pages, edit their block layout, publish,
 * set homepage; public site renders the result.
 */
class PageBuilderTest extends TestCase
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
        SiteSetting::create(['school_id' => $this->school->id, 'site_name' => 'Test School']);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/pages')->assertOk();
        $this->get('/admin/pages/create')->assertOk();
    }

    public function test_create_page_seeds_a_layout(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/pages', ['title' => 'About Us', 'template' => 'full'])->assertRedirect();

        $page = Page::first();
        $this->assertNotNull($page);
        $this->assertSame('about-us', $page->slug);
        $this->assertDatabaseHas('page_layouts', ['page_id' => $page->id]);

        // The block editor screen renders (add-block controls + templates).
        $this->get("/admin/pages/{$page->id}/edit")->assertOk()->assertSee('Content Blocks');
    }

    public function test_save_blocks_publishes_and_renders(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/pages', ['title' => 'History', 'template' => 'full']);
        $page = Page::first();

        $this->put("/admin/pages/{$page->id}", [
            'title' => 'Our History', 'slug' => 'history', 'status' => 'published', 'template' => 'full',
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'A proud history']],
                ['type' => 'richtext', 'data' => ['heading' => 'Since 1985', 'html' => '<p>Founded long ago.</p>']],
                ['type' => 'gallery_photo', 'data' => ['heading' => 'Gallery', 'images' => "https://x/a.jpg\nhttps://x/b.jpg"]],
                ['type' => 'evil', 'data' => []], // unknown type dropped
            ],
        ])->assertRedirect();

        $layout = PageLayout::where('page_id', $page->id)->where('is_published', true)->latest('id')->first();
        $this->assertNotNull($layout);
        $json = $layout->layout_json;
        $this->assertSame('full', $json['template']);
        $this->assertCount(3, $json['blocks']); // evil dropped
        $this->assertSame(['https://x/a.jpg', 'https://x/b.jpg'], $json['blocks'][2]['data']['images']); // multiline → array

        // Public render
        $this->get('/history')->assertOk()
            ->assertSee('A proud history')
            ->assertSee('Founded long ago.')
            ->assertSee('https://x/a.jpg', false);
    }

    public function test_sidebar_links_parse_into_pairs(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/pages', ['title' => 'Contact', 'template' => 'sidebar']);
        $page = Page::first();

        $this->put("/admin/pages/{$page->id}", [
            'title' => 'Contact', 'slug' => 'contact', 'status' => 'published', 'template' => 'sidebar',
            'blocks' => [['type' => 'contact', 'data' => ['heading' => 'Reach us']]],
            'sidebar' => [['type' => 'quick_links', 'data' => ['heading' => 'Links', 'links' => "Notices|/notices\nStaff|/staff"]]],
        ])->assertRedirect();

        $json = PageLayout::where('page_id', $page->id)->where('is_published', true)->latest('id')->first()->layout_json;
        $this->assertSame(
            [['label' => 'Notices', 'url' => '/notices'], ['label' => 'Staff', 'url' => '/staff']],
            $json['sidebar'][0]['data']['links'],
        );

        $this->get('/contact')->assertOk()->assertSee('Reach us')->assertSee('Notices');
    }

    public function test_set_homepage_and_delete(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/pages', ['title' => 'Welcome', 'template' => 'full']);
        $page = Page::first();

        $this->post("/admin/pages/{$page->id}/homepage")->assertRedirect();
        $this->assertDatabaseHas('pages', ['id' => $page->id, 'is_homepage' => true]);
        $this->assertDatabaseHas('site_settings', ['school_id' => $this->school->id, 'homepage_page_id' => $page->id]);

        $this->delete("/admin/pages/{$page->id}")->assertRedirect();
        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
    }
}
