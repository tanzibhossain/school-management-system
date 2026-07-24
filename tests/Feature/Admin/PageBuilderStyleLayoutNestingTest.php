<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\PageRenderService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the two areas PageBuilderTest.php doesn't: a block's Style/Layout
 * tab values (PageRenderService::sanitizeStyle()/sanitizeLayout() +
 * BlockPresentation) and Container/Grid nesting (recursive normalize/render,
 * depth cap, unknown-type dropping at every level) — see §7d/§7g in
 * docs/modules/28-elementor-block-editor-plan.md. Previously zero coverage
 * existed for either.
 */
class PageBuilderStyleLayoutNestingTest extends TestCase
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

    private function publish(array $blocks, array $sidebar = [], string $template = 'full'): Page
    {
        $this->post('/admin/pages', ['title' => 'Style Page', 'template' => $template]);
        $page = Page::latest('id')->first();

        $this->put("/admin/pages/{$page->id}", [
            'title' => 'Style Page', 'slug' => $page->slug, 'status' => 'published', 'template' => $template,
            'blocks' => $blocks, 'sidebar' => $sidebar,
        ])->assertRedirect();

        return $page->fresh();
    }

    // ── Style tab ────────────────────────────────────────────────────────

    public function test_style_values_are_sanitized_and_rendered(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading',
            'data' => ['text' => 'Styled Heading'],
            'style' => [
                'bg_color' => '#112233',
                'text_color' => 'not-a-hex', // invalid — dropped
                'padding_top' => '9999',      // clamped to 400
                'radius' => '12',
                'shadow' => 'md',
                'animation' => 'up',
            ],
        ]]);

        $layout = PageLayout::where('page_id', $page->id)->where('is_published', true)->latest('id')->first();
        $style = $layout->layout_json['blocks'][0]['style'];

        $this->assertSame('#112233', $style['bg_color']);
        $this->assertArrayNotHasKey('text_color', $style); // invalid hex never stored
        $this->assertSame(400, $style['padding_top']);     // clamped
        $this->assertSame('md', $style['shadow']);

        $this->get('/'.$page->slug)->assertOk()
            ->assertSee('background-color:#112233', false)
            ->assertSee('border-radius:12px', false)
            ->assertSee('reveal-up', false);
    }

    public function test_invalid_shadow_and_animation_values_are_dropped(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading',
            'data' => ['text' => 'X'],
            'style' => ['shadow' => 'huge', 'animation' => 'spin'],
        ]]);

        $style = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['style'];

        $this->assertArrayNotHasKey('shadow', $style);
        $this->assertArrayNotHasKey('animation', $style);
    }

    // ── Layout tab ───────────────────────────────────────────────────────

    public function test_layout_hide_and_columns_render_bootstrap_utility_classes(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading',
            'data' => ['text' => 'Hidden On Mobile'],
            'layout' => ['hide' => ['mobile' => '1', 'desktop' => '0']],
        ]]);

        $json = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json;
        $this->assertTrue($json['blocks'][0]['layout']['hide']['mobile']);
        $this->assertFalse($json['blocks'][0]['layout']['hide']['desktop']);

        // BlockPresentation::visibilityClasses() — hidden on mobile (base),
        // visible (d-block) at every wider breakpoint.
        $this->get('/'.$page->slug)->assertOk()
            ->assertSee('d-none', false)
            ->assertSee('d-xl-block', false);
    }

    public function test_layout_columns_are_clamped_to_1_through_6(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'staff',
            'data' => ['heading' => 'Our Staff'],
            'layout' => ['columns' => ['mobile' => '0', 'desktop' => '99']],
        ]]);

        $columns = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['layout']['columns'];

        $this->assertSame(1, $columns['mobile']);
        $this->assertSame(6, $columns['desktop']);
    }

    // ── Container/Grid nesting ──────────────────────────────────────────

    public function test_container_holds_nested_children_and_renders_them(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'container',
            'data' => [
                'direction' => 'row',
                'blocks' => [
                    ['type' => 'heading', 'data' => ['text' => 'Nested Heading']],
                    ['type' => 'richtext', 'data' => ['html' => '<p>Nested paragraph</p>']],
                ],
            ],
        ]]);

        $json = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json;
        $this->assertCount(2, $json['blocks'][0]['data']['blocks']);
        $this->assertSame('heading', $json['blocks'][0]['data']['blocks'][0]['type']);

        $this->get('/'.$page->slug)->assertOk()
            ->assertSee('Nested Heading')
            ->assertSee('Nested paragraph');
    }

    public function test_container_can_nest_another_container(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'container',
            'data' => ['blocks' => [[
                'type' => 'container',
                'data' => ['blocks' => [
                    ['type' => 'heading', 'data' => ['text' => 'Two Levels Deep']],
                ]],
            ]]],
        ]]);

        $json = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json;
        $inner = $json['blocks'][0]['data']['blocks'][0];
        $this->assertSame('container', $inner['type']);
        $this->assertSame('heading', $inner['data']['blocks'][0]['type']);

        $this->get('/'.$page->slug)->assertOk()->assertSee('Two Levels Deep');
    }

    public function test_unknown_block_type_is_dropped_at_every_nesting_level(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'container',
            'data' => ['blocks' => [
                ['type' => 'heading', 'data' => ['text' => 'Kept']],
                ['type' => 'evil', 'data' => []],
                [
                    'type' => 'container',
                    'data' => ['blocks' => [
                        ['type' => 'evil-nested', 'data' => []],
                        ['type' => 'heading', 'data' => ['text' => 'Also Kept']],
                    ]],
                ],
            ]],
        ]]);

        $json = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json;
        $topChildren = $json['blocks'][0]['data']['blocks'];
        $this->assertCount(2, $topChildren); // 'evil' dropped
        $innerChildren = $topChildren[1]['data']['blocks'];
        $this->assertCount(1, $innerChildren); // 'evil-nested' dropped
    }

    public function test_nesting_beyond_max_depth_drops_container_type_and_terminates(): void
    {
        $this->actingAs($this->admin);

        // Build MAX_NESTING_DEPTH + 2 levels of containers — deeper than the
        // cap allows. normalizeBlocks() must still terminate (no crash/loop)
        // and stop accepting 'container' once the cap is reached.
        $depth = PageRenderService::MAX_NESTING_DEPTH + 2;
        $blocks = [['type' => 'heading', 'data' => ['text' => 'Deepest']]];
        for ($i = 0; $i < $depth; $i++) {
            $blocks = [['type' => 'container', 'data' => ['blocks' => $blocks]]];
        }

        $page = $this->publish($blocks);

        $json = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json;

        // Walk down as far as the stored tree actually goes.
        $node = $json['blocks'][0];
        $levels = 0;
        while ($node['type'] === 'container' && ! empty($node['data']['blocks'])) {
            $node = $node['data']['blocks'][0];
            $levels++;
        }

        $this->assertLessThanOrEqual(PageRenderService::MAX_NESTING_DEPTH, $levels);
        $this->get('/'.$page->slug)->assertOk(); // never crashes, however deep the input was
    }

    public function test_grid_nested_child_style_and_layout_survive_a_round_trip(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'grid',
            'data' => ['blocks' => [[
                'type' => 'heading',
                'data' => ['text' => 'Grid Child'],
                'style' => ['bg_color' => '#abcdef'],
                'layout' => ['hide' => ['mobile' => '1']],
            ]]],
        ]]);

        $child = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['data']['blocks'][0];

        $this->assertSame('#abcdef', $child['style']['bg_color']);
        $this->assertTrue($child['layout']['hide']['mobile']);

        // Re-opening the editor reverses the stored layout back into
        // editable form (PageController::layoutForEditor()) without losing
        // the nested child's own style/layout — the editor screen just
        // needs to render without error for this page.
        $this->get("/admin/pages/{$page->id}/edit")->assertOk();
    }
}
