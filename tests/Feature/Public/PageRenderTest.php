<?php

namespace Tests\Feature\Public;

use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public block-based page rendering: templates (full / sidebar), core blocks,
 * homepage wiring, dynamic staff block, and slug 404s.
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
            'address' => '1 School Lane', 'email' => 'hello@test.school',
        ]);
        SiteSetting::create(['school_id' => $this->school->id, 'site_name' => 'Test School']);
    }

    private function publishPage(string $slug, string $title, array $layout, bool $homepage = false): Page
    {
        $page = Page::create([
            'school_id' => $this->school->id, 'slug' => $slug, 'title' => $title,
            'status' => 'published', 'is_homepage' => $homepage,
        ]);
        PageLayout::create([
            'school_id' => $this->school->id, 'page_id' => $page->id,
            'layout_json' => $layout, 'is_published' => true, 'published_at' => now(),
        ]);

        return $page;
    }

    public function test_full_width_page_renders_blocks(): void
    {
        $this->publishPage('history', 'Our History', [
            'template' => 'full',
            'blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'A proud history']],
                ['type' => 'richtext', 'data' => ['heading' => 'Since 1942', 'html' => '<p>Founded in nineteen forty two.</p>']],
            ],
        ]);

        $this->get('/history')
            ->assertOk()
            ->assertSee('A proud history')
            ->assertSee('Since 1942')
            ->assertSee('Founded in nineteen forty two.');
    }

    public function test_sidebar_template_renders_main_and_sidebar(): void
    {
        $this->publishPage('contact', 'Contact', [
            'template' => 'sidebar',
            'blocks' => [
                ['type' => 'contact', 'data' => ['heading' => 'Reach out', 'phone' => '01700000000']],
            ],
            'sidebar' => [
                ['type' => 'quick_links', 'data' => ['heading' => 'Links', 'links' => [['label' => 'Notices', 'url' => '/notices']]]],
                ['type' => 'contact_info', 'data' => ['heading' => 'Find us']],
            ],
        ]);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Reach out')
            ->assertSee('01700000000')
            ->assertSee('Links')
            ->assertSee('Find us')
            ->assertSee('1 School Lane'); // contact_info pulls the school address
    }

    public function test_staff_block_renders_live_staff(): void
    {
        Staff::create([
            'school_id' => $this->school->id, 'employee_id' => 'EMP-1', 'name' => 'Professor Plum',
            'gender' => 'male', 'status' => 'active', 'joining_date' => now()->subYear(),
        ]);

        $this->publishPage('teachers', 'Teachers', [
            'template' => 'full',
            'blocks' => [['type' => 'staff', 'data' => ['heading' => 'Our teachers']]],
        ]);

        $this->get('/teachers')->assertOk()->assertSee('Our teachers')->assertSee('Professor Plum');
    }

    public function test_homepage_layout_drives_root(): void
    {
        $this->publishPage('welcome', 'Welcome', [
            'template' => 'full',
            'blocks' => [['type' => 'hero', 'data' => ['title' => 'Welcome to our campus', 'subtitle' => 'Learn and grow']]],
        ], homepage: true);

        $this->get('/')->assertOk()->assertSee('Welcome to our campus')->assertSee('Learn and grow');
    }

    public function test_unknown_and_unpublished_slugs_404(): void
    {
        // Draft page — not published, must not be publicly visible.
        $page = Page::create([
            'school_id' => $this->school->id, 'slug' => 'secret', 'title' => 'Secret', 'status' => 'draft',
        ]);
        PageLayout::create([
            'school_id' => $this->school->id, 'page_id' => $page->id,
            'layout_json' => ['template' => 'full', 'blocks' => []], 'is_published' => false,
        ]);

        $this->get('/secret')->assertNotFound();
        $this->get('/does-not-exist')->assertNotFound();
    }

    public function test_unknown_block_type_is_ignored(): void
    {
        $this->publishPage('mixed', 'Mixed', [
            'template' => 'full',
            'blocks' => [
                ['type' => 'evil_script', 'data' => []],
                ['type' => 'heading', 'data' => ['text' => 'Still here']],
            ],
        ]);

        $this->get('/mixed')->assertOk()->assertSee('Still here');
    }
}
