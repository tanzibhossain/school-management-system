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

    /**
     * PageRenderService::renderPage() caches the resolved view keyed by the
     * PUBLISHED PageLayout's own id (see §7l in
     * docs/modules/28-elementor-block-editor-plan.md) — every publish()
     * creates a brand-new PageLayout row, so re-publishing with different
     * content must show the new content immediately, not a stale cached
     * render of the old layout id.
     */
    public function test_republishing_does_not_serve_a_stale_cached_render(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/pages', ['title' => 'Notice', 'template' => 'full']);
        $page = Page::first();

        $this->put("/admin/pages/{$page->id}", [
            'title' => 'Notice', 'slug' => 'notice', 'status' => 'published', 'template' => 'full',
            'blocks' => [['type' => 'heading', 'data' => ['text' => 'Version One']]],
        ])->assertRedirect();
        $this->get('/notice')->assertOk()->assertSee('Version One')->assertDontSee('Version Two');

        $this->put("/admin/pages/{$page->id}", [
            'title' => 'Notice', 'slug' => 'notice', 'status' => 'published', 'template' => 'full',
            'blocks' => [['type' => 'heading', 'data' => ['text' => 'Version Two']]],
        ])->assertRedirect();
        $this->get('/notice')->assertOk()->assertSee('Version Two')->assertDontSee('Version One');
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

    /**
     * Optimistic concurrency check (see §7m in
     * docs/modules/28-elementor-block-editor-plan.md): a second admin's save
     * arrives carrying a stale known_layout_id (captured when THEIR editor
     * loaded, before the first admin's save created a newer revision).
     * Neither admin's work is discarded — both revisions must exist — but
     * the second (conflicting) save must NOT auto-publish and must flash a
     * warning instead of the normal success message.
     */
    public function test_concurrent_save_keeps_both_revisions_and_warns_instead_of_overwriting(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/pages', ['title' => 'Race', 'template' => 'full']);
        $page = Page::first();

        // Both "admins" loaded the editor at the same moment — the page's
        // only layout so far is the empty one seeded by store().
        $seedLayoutId = $page->layouts()->first()->id;

        // Admin A saves+publishes first — succeeds normally.
        $this->put("/admin/pages/{$page->id}", [
            'title' => 'Race', 'slug' => $page->slug, 'status' => 'published', 'template' => 'full',
            'known_layout_id' => $seedLayoutId,
            'blocks' => [['type' => 'heading', 'data' => ['text' => 'Admin A version']]],
        ])->assertRedirect()->assertSessionHas('status');

        $afterA = PageLayout::where('page_id', $page->id)->latest('id')->first();
        $this->assertSame('Admin A version', $afterA->layout_json['blocks'][0]['data']['text']);

        // Admin B's browser still only knows about the original seed layout
        // — their save arrives with the now-stale known_layout_id.
        $response = $this->put("/admin/pages/{$page->id}", [
            'title' => 'Race', 'slug' => $page->slug, 'status' => 'published', 'template' => 'full',
            'known_layout_id' => $seedLayoutId,
            'blocks' => [['type' => 'heading', 'data' => ['text' => 'Admin B version']]],
        ]);
        $response->assertRedirect()->assertSessionHas('warning');

        // Both A's and B's revisions exist — nothing was discarded.
        $this->assertSame(3, PageLayout::where('page_id', $page->id)->count()); // seed + A + B

        // The PUBLISHED revision is still Admin A's — B's conflicting save
        // was kept as an unpublished draft, never auto-published over A's.
        $published = PageLayout::where('page_id', $page->id)->where('is_published', true)->first();
        $this->assertSame('Admin A version', $published->layout_json['blocks'][0]['data']['text']);

        $bDraft = PageLayout::where('page_id', $page->id)->where('is_published', false)->latest('id')->first();
        $this->assertSame('Admin B version', $bDraft->layout_json['blocks'][0]['data']['text']);
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
