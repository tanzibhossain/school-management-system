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

    public function test_all_four_padding_and_margin_sides_are_sanitized_and_rendered(): void
    {
        // Padding/margin moved to the Layout tab's 4-box spacing control
        // (top/bottom/left/right) — still [style][*] keys underneath, see
        // §7x in docs/modules/28-elementor-block-editor-plan.md.
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading',
            'data' => ['text' => 'Spaced Heading'],
            'style' => [
                'padding_top' => '10', 'padding_bottom' => '20', 'padding_left' => '30', 'padding_right' => '9999',
                'margin_top' => '5', 'margin_bottom' => '15', 'margin_left' => '25', 'margin_right' => '35',
            ],
        ]]);

        $style = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['style'];

        $this->assertSame(10, $style['padding_top']);
        $this->assertSame(20, $style['padding_bottom']);
        $this->assertSame(30, $style['padding_left']);
        $this->assertSame(400, $style['padding_right']); // clamped, same as padding_top elsewhere
        $this->assertSame(5, $style['margin_top']);
        $this->assertSame(15, $style['margin_bottom']);
        $this->assertSame(25, $style['margin_left']);
        $this->assertSame(35, $style['margin_right']);

        $this->get('/'.$page->slug)->assertOk()
            ->assertSee('padding-top:10px', false)
            ->assertSee('padding-bottom:20px', false)
            ->assertSee('padding-left:30px', false)
            ->assertSee('padding-right:400px', false)
            ->assertSee('margin-top:5px', false)
            ->assertSee('margin-bottom:15px', false)
            ->assertSee('margin-left:25px', false)
            ->assertSee('margin-right:35px', false);
    }

    // ── Advanced tab: width, border, radius (§7aa) ──────────────────────────

    public function test_width_full_mode_renders_100_percent(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'Full Width'],
            'style' => ['width_mode' => 'full'],
        ]]);
        $this->get('/'.$page->slug)->assertOk()->assertSee('width:100%', false);
    }

    public function test_width_inline_mode_renders_auto_and_inline_block(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'Inline'],
            'style' => ['width_mode' => 'inline'],
        ]]);
        $this->get('/'.$page->slug)->assertOk()
            ->assertSee('display:inline-block', false)
            ->assertSee('width:auto', false);
    }

    public function test_custom_width_requires_a_value_and_defaults_to_percent(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'Custom Width'],
            'style' => ['width_mode' => 'custom', 'width_value' => '75'],
        ]]);

        $style = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['style'];
        $this->assertSame('custom', $style['width_mode']);
        // assertEquals (not assertSame): sanitizeStyle() casts width_value
        // through (float) so 33.5% etc. survive, but MySQL's native JSON
        // column type normalizes a whole-number double back to a bare
        // integer on round-trip — 75.0 in, 75 (int) out — so the numeric
        // TYPE isn't part of this contract, only the value. The rendered
        // CSS ('width:75%', asserted below) is identical either way.
        $this->assertEquals(75.0, $style['width_value']);
        $this->assertSame('%', $style['width_unit']); // default unit when none given

        $this->get('/'.$page->slug)->assertOk()->assertSee('width:75%', false);
    }

    public function test_custom_width_with_no_value_stores_no_width_at_all(): void
    {
        // width_mode=custom with a blank value is not a valid "give it some
        // width" instruction — nothing should render, not "width:0px".
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'Custom, Unset'],
            'style' => ['width_mode' => 'custom', 'width_value' => ''],
        ]]);

        $style = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['style'];
        $this->assertArrayNotHasKey('width_value', $style);

        // Not assertDontSee('width:') — the page's own editor-preview bridge
        // script unconditionally embeds "min-width:170px" (the right-click
        // context menu's inline style, see public/blocks/render.blade.php's
        // gated script), which contains "width:" as a substring and would
        // make a plain assertDontSee false-positive-fail on every page,
        // regardless of this block's own CSS. A negative-lookbehind regex
        // targets only a real width/min-width/max-width CSS declaration.
        $html = $this->get('/'.$page->slug)->assertOk()->getContent();
        $this->assertDoesNotMatchRegularExpression('/(?<!min-)(?<!max-)width:/', $html);
    }

    public function test_border_is_only_rendered_when_a_real_style_is_set(): void
    {
        // A border-width with no border-style is invisible per the CSS spec
        // (default style is 'none') — sanitizeStyle() drops width/color
        // entirely whenever style is missing or explicitly 'none', so this
        // can never produce a "half-configured", invisible border.
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'No Border'],
            'style' => [
                'border_style' => 'none',
                'border_width_top' => '5', 'border_color' => '#ff0000',
            ],
        ]]);

        $style = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['style'];
        $this->assertArrayNotHasKey('border_width_top', $style);
        $this->assertArrayNotHasKey('border_color', $style);

        $this->get('/'.$page->slug)->assertOk()->assertDontSee('border-style:', false);
    }

    public function test_border_with_a_real_style_renders_width_and_color_per_side(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'Bordered'],
            'style' => [
                'border_style' => 'dashed',
                'border_width_top' => '2', 'border_width_bottom' => '999', // clamped to 50
                'border_color' => '#00ff00',
            ],
        ]]);

        $style = PageLayout::where('page_id', $page->id)->where('is_published', true)
            ->latest('id')->first()->layout_json['blocks'][0]['style'];
        $this->assertSame('dashed', $style['border_style']);
        $this->assertSame(2, $style['border_width_top']);
        $this->assertSame(50, $style['border_width_bottom']); // clamped — 50, not 400 like padding

        $this->get('/'.$page->slug)->assertOk()
            ->assertSee('border-style:dashed', false)
            ->assertSee('border-top-width:2px', false)
            ->assertSee('border-bottom-width:50px', false)
            ->assertSee('border-color:#00ff00', false);
    }

    public function test_per_side_radius_wins_over_legacy_single_radius(): void
    {
        // A page saved before §7aa (single 'radius') still renders correctly
        // via BlockPresentation's fallback (see
        // test_style_values_are_sanitized_and_rendered above, which already
        // covers that legacy-only case) — this test covers the NEW per-side
        // case taking priority once any one side is actually set.
        $this->actingAs($this->admin);
        $page = $this->publish([[
            'type' => 'heading', 'data' => ['text' => 'Rounded'],
            'style' => ['radius' => '12', 'radius_top' => '30'],
        ]]);

        $response = $this->get('/'.$page->slug);
        $response->assertOk();
        $response->assertSee('border-top-radius:30px', false);
        $response->assertDontSee('border-radius:12px', false);
    }

    public function test_editor_shows_the_advanced_tab_with_its_four_sections(): void
    {
        $this->actingAs($this->admin);
        $page = $this->publish([['type' => 'heading', 'data' => ['text' => 'X']]]);

        $this->get("/admin/pages/{$page->id}/edit")->assertOk()
            ->assertSee('Advanced')
            ->assertSee('Border')
            ->assertSee('Background')
            ->assertSee('Responsive');
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
