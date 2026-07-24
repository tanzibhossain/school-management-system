<?php

namespace Tests\Feature\Public;

use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public page SEO meta tags — Open Graph (§7 per-page SEO wiring, task #55)
 * and Twitter Card (docs/modules/28-elementor-block-editor-plan.md §7t),
 * which mirror the same precedence for both the og: and twitter: tags: a
 * page's own meta_desc/og_image win over the site-wide Website > Settings default.
 */
class PageSeoMetaTagsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        SiteSetting::create(['school_id' => $this->school->id, 'site_name' => 'Test School']);
    }

    private function publishPage(array $attrs): Page
    {
        $page = Page::create(array_merge([
            'school_id' => $this->school->id, 'slug' => 'about', 'title' => 'About Us',
            'status' => 'published',
        ], $attrs));
        PageLayout::create([
            'school_id' => $this->school->id, 'page_id' => $page->id,
            'layout_json' => ['template' => 'full', 'blocks' => []],
            'is_published' => true, 'published_at' => now(),
        ]);

        return $page;
    }

    public function test_page_with_meta_desc_and_og_image_emits_matching_twitter_and_og_tags(): void
    {
        $this->publishPage([
            'meta_desc' => 'Learn about our history and mission.',
            'og_image' => 'https://example.com/about-og.jpg',
        ]);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('About Us · Test School', false);
        $response->assertSee('<meta property="og:description" content="Learn about our history and mission.">', false);
        $response->assertSee('<meta property="og:image" content="https://example.com/about-og.jpg">', false);
        $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
        $response->assertSee('<meta name="twitter:description" content="Learn about our history and mission.">', false);
        $response->assertSee('<meta name="twitter:image" content="https://example.com/about-og.jpg">', false);
    }

    public function test_page_without_og_image_falls_back_to_summary_twitter_card(): void
    {
        $this->publishPage(['meta_desc' => 'No image on this one.']);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('<meta name="twitter:card" content="summary">', false);
        $response->assertDontSee('name="twitter:image"', false);
    }

    public function test_page_title_is_escaped_consistently_across_title_og_and_twitter_tags(): void
    {
        // A title containing a double quote would previously have broken the
        // (unescaped) og:title/twitter:title attribute — see §7t's fix.
        $this->publishPage(['title' => 'Rules & "Regulations"']);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('<title>Rules &amp; &quot;Regulations&quot; · Test School</title>', false);
        $response->assertSee('<meta property="og:title" content="Rules &amp; &quot;Regulations&quot; · Test School">', false);
        $response->assertSee('<meta name="twitter:title" content="Rules &amp; &quot;Regulations&quot; · Test School">', false);
    }
}
