<?php

namespace App\Modules\Website\Services;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Website\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Turns a page's stored layout_json (an ordered list of typed blocks + a
 * template choice) into the data a Blade view needs, resolving the "dynamic"
 * blocks (notices, stats, staff) against live module data at render time.
 *
 * layout_json shape:
 *   { "template": "full"|"sidebar", "blocks": [ {type,data} ], "sidebar": [ {type,data} ] }
 */
class PageRenderService
{
    /** Every block type the builder/renderer understands (main column). */
    public const BLOCKS = [
        'hero' => 'Hero banner',
        'heading' => 'Heading',
        'richtext' => 'Text editor',
        'image' => 'Image',
        'video' => 'Video',
        'button' => 'Button',
        'divider' => 'Divider',
        'spacer' => 'Spacer',
        'google_maps' => 'Google Maps',
        'icon' => 'Icon',
        'image_text' => 'Image + text',
        'staff' => 'Staff list',
        'notices' => 'Notices',
        'stats' => 'Statistics',
        'gallery_photo' => 'Photo gallery',
        'gallery_video' => 'Video gallery',
        'admission_form' => 'Admission form',
        'contact' => 'Contact',
        'container' => 'Container',
        'grid' => 'Grid',
    ];

    /**
     * Nesting is now recursive — a container/grid CAN hold another
     * container/grid — but not infinitely: past MAX_NESTING_DEPTH levels,
     * LEAF_BLOCKS (below) becomes the allow-list instead of the full BLOCKS
     * list, so a container/grid type is simply not accepted anymore at that
     * depth and the tree is guaranteed to terminate. See §7g in
     * docs/modules/28-elementor-block-editor-plan.md.
     */
    public const MAX_NESTING_DEPTH = 6;

    /**
     * BLOCKS minus 'container'/'grid' — the allow-list used once nesting has
     * reached MAX_NESTING_DEPTH (see above). Used by normalizeBlocks()/
     * cleanBlocks()/resolveNestedBlocks() wherever nested block data is
     * processed.
     */
    public const LEAF_BLOCKS = [
        'hero' => 'Hero banner',
        'heading' => 'Heading',
        'richtext' => 'Text editor',
        'image' => 'Image',
        'video' => 'Video',
        'button' => 'Button',
        'divider' => 'Divider',
        'spacer' => 'Spacer',
        'google_maps' => 'Google Maps',
        'icon' => 'Icon',
        'image_text' => 'Image + text',
        'staff' => 'Staff list',
        'notices' => 'Notices',
        'stats' => 'Statistics',
        'gallery_photo' => 'Photo gallery',
        'gallery_video' => 'Video gallery',
        'admission_form' => 'Admission form',
        'contact' => 'Contact',
    ];

    /** Sidebar-only block types. */
    public const SIDEBAR_BLOCKS = [
        'quick_links' => 'Quick links',
        'office_hours' => 'Office hours',
        'contact_info' => 'Contact info',
        'recent_notices' => 'Recent notices',
    ];

    /**
     * Groups BLOCKS into the Add Block panel's categories (see
     * admin/website/pages/edit.blade.php). Any BLOCKS key not listed here
     * falls back to 'advanced' — see edit.blade.php's category-building loop.
     */
    public const CATEGORIES = [
        'layout' => ['container', 'grid'],
        'basic' => ['heading', 'image', 'richtext', 'video', 'button', 'divider', 'spacer', 'google_maps', 'icon'],
    ];

    public function __construct(private readonly PublicPortalService $portal) {}

    /**
     * Normalise a raw layout_json array into a safe, render-ready structure.
     *
     * @param  array<string, mixed>|null  $layout
     * @return array{template: string, blocks: array<int, array{type: string, data: array, style: array, layout: array}>, sidebar: array<int, array{type: string, data: array, style: array, layout: array}>}
     */
    public function normalize(?array $layout): array
    {
        $template = ($layout['template'] ?? 'full') === 'sidebar' ? 'sidebar' : 'full';

        return [
            'template' => $template,
            'blocks' => $this->cleanBlocks($layout['blocks'] ?? [], self::BLOCKS),
            'sidebar' => $template === 'sidebar'
                ? $this->cleanBlocks($layout['sidebar'] ?? [], self::SIDEBAR_BLOCKS)
                : [],
        ];
    }

    /**
     * Resolve the live data a dynamic block needs (notices/stats/staff). Static
     * blocks (text/image) just return their own stored data.
     *
     * @param  array{type: string, data: array}  $block
     * @return array<string, mixed>
     */
    public function resolveBlockData(int $schoolId, array $block, int $depth = 0): array
    {
        $data = $block['data'] ?? [];

        return match ($block['type']) {
            'notices', 'recent_notices' => $data + ['notices' => $this->portal->notices($schoolId)],
            'stats' => $data + ['stats' => $this->portal->stats($schoolId)],
            'staff' => $data + ['members' => $this->staffFor($schoolId, $data)],
            'contact_info', 'contact' => $data + ['school' => School::find($schoolId)],
            'admission_form' => $data + [
                'classes' => SchoolClass::where('school_id', $schoolId)
                    ->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
                'years' => AcademicYear::where('school_id', $schoolId)
                    ->where('is_trash', false)->orderByDesc('is_current')->orderByDesc('year')->get(['id', 'year']),
                'field_data' => $this->prepareAdmissionFormFields($data['fields'] ?? $data['hidden'] ?? []),
            ],
            // array_merge(), not `+`: $data already has its OWN 'blocks' key
            // (the raw, unresolved stored children) — `+` only fills in
            // MISSING keys, so it would silently keep the raw array and
            // discard the resolved one below, leaving every child without
            // its 'd' key (undefined array key at render time). array_merge()
            // correctly lets the resolved value win.
            'container', 'grid' => array_merge($data, [
                'blocks' => $this->resolveNestedBlocks($schoolId, is_array($data['blocks'] ?? null) ? $data['blocks'] : [], $depth + 1),
            ]),
            default => $data,
        };
    }

    /**
     * Resolve a container/grid's own children into the same {type,d,style,
     * layout} shape buildViewFromBlocks() produces for top-level blocks, so
     * public/blocks/render.blade.php can recursively @include itself for
     * each one — genuinely recursively: a child that is itself
     * container/grid gets its OWN 'blocks' resolved the same way, via the
     * mutual recursion with resolveBlockData() above, up to
     * MAX_NESTING_DEPTH (beyond which the allow-list drops to LEAF_BLOCKS,
     * so a container/grid type simply stops being accepted and the
     * recursion terminates). $depth is the depth of the children being
     * resolved here (1 for a top-level container's own children, 2 for
     * their children's children, …).
     *
     * $blocks is arbitrary decoded JSON from layout_json (not a statically
     * guaranteed shape — a stored container's children could in principle
     * be missing/malformed 'type' entries), so this stays loosely typed
     * rather than the {type:string,...} shape buildViewFromBlocks() returns,
     * to keep the is_string()/array_key_exists() guard below meaningful.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return array<int, array{type: string, d: array, style: array, layout: array}>
     */
    private function resolveNestedBlocks(int $schoolId, array $blocks, int $depth): array
    {
        $allowed = $depth >= self::MAX_NESTING_DEPTH ? self::LEAF_BLOCKS : self::BLOCKS;
        $out = [];
        foreach ($blocks as $b) {
            $type = $b['type'] ?? null;
            if (! is_string($type) || ! array_key_exists($type, $allowed)) {
                continue;
            }
            $out[] = [
                'type' => $type,
                'd' => $this->resolveBlockData($schoolId, $b, $depth),
                'style' => $b['style'] ?? [],
                'layout' => $b['layout'] ?? [],
            ];
        }

        return $out;
    }

    /**
     * Staff list honouring the block's category filter. Categories map to the
     * real staff data: "teachers"/"employees" via designation grouping is
     * school-defined, so we filter by designation_id/department_id when given,
     * else return all active staff.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, Staff>
     */
    private function staffFor(int $schoolId, array $data): Collection
    {
        $filters = [];
        if (! empty($data['designation_id'])) {
            $filters['designation_id'] = (int) $data['designation_id'];
        }
        if (! empty($data['department_id'])) {
            $filters['department_id'] = (int) $data['department_id'];
        }

        return $this->portal->staffList($schoolId, $filters);
    }

    /**
     * Drop any block whose type isn't in the allow-list and coerce data to
     * array. Recurses into a container/grid's own children (genuinely, to
     * MAX_NESTING_DEPTH — see the constant's docblock) so a container nested
     * inside a container is cleaned the same way as a top-level one.
     *
     * @param  array<int, mixed>  $blocks
     * @param  array<string, string>  $allowed
     * @return array<int, array{type: string, data: array, style: array, layout: array}>
     */
    private function cleanBlocks(array $blocks, array $allowed, int $depth = 0): array
    {
        $childAllowed = $depth + 1 >= self::MAX_NESTING_DEPTH ? self::LEAF_BLOCKS : self::BLOCKS;
        $out = [];
        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            if (! is_string($type) || ! array_key_exists($type, $allowed)) {
                continue;
            }
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            if (in_array($type, ['container', 'grid'], true)) {
                $data['blocks'] = $this->cleanBlocks(is_array($data['blocks'] ?? null) ? $data['blocks'] : [], $childAllowed, $depth + 1);
            }
            $out[] = [
                'type' => $type,
                'data' => $data,
                'style' => self::sanitizeStyle(is_array($block['style'] ?? null) ? $block['style'] : []),
                'layout' => self::sanitizeLayout(is_array($block['layout'] ?? null) ? $block['layout'] : []),
            ];
        }

        return $out;
    }

    /**
     * Clamp/coerce a block's Style-tab values to safe, known-shape data — the
     * one place that decides what's a legal style value, shared by the admin
     * save path (PageController::normalizeBlocks) and this render path, so
     * stored layout_json can never carry something the renderer doesn't
     * expect (a stray CSS injection, an out-of-range opacity, etc.).
     *
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    public static function sanitizeStyle(array $style): array
    {
        $px = fn ($v) => $v === null || $v === '' ? null : max(0, min(400, (int) $v));
        $hex = fn ($v) => is_string($v) && preg_match('/^#[0-9a-fA-F]{3,8}$/', trim($v)) ? trim($v) : null;
        $url = fn ($v) => is_string($v) && trim($v) !== '' ? trim($v) : null;

        return array_filter([
            'padding_top' => $px($style['padding_top'] ?? null),
            'padding_bottom' => $px($style['padding_bottom'] ?? null),
            'margin_top' => $px($style['margin_top'] ?? null),
            'margin_bottom' => $px($style['margin_bottom'] ?? null),
            'bg_color' => $hex($style['bg_color'] ?? null),
            'bg_image' => $url($style['bg_image'] ?? null),
            'bg_overlay' => max(0, min(100, (int) ($style['bg_overlay'] ?? 0))),
            'text_color' => $hex($style['text_color'] ?? null),
            'radius' => $px($style['radius'] ?? null),
            'shadow' => in_array($style['shadow'] ?? null, ['sm', 'md', 'lg'], true) ? $style['shadow'] : null,
            'animation' => in_array($style['animation'] ?? null, ['fade', 'up'], true) ? $style['animation'] : null,
        ], fn ($v) => $v !== null);
    }

    /**
     * Clamp/coerce a block's Layout-tab values (per-breakpoint column count
     * and visibility) — same reasoning as sanitizeStyle().
     *
     * @param  array<string, mixed>  $layout
     * @return array{columns: array<string, int>, hide: array<string, bool>}
     */
    public static function sanitizeLayout(array $layout): array
    {
        $breakpoints = ['mobile', 'tablet', 'laptop', 'desktop'];
        $columns = is_array($layout['columns'] ?? null) ? $layout['columns'] : [];
        $hide = is_array($layout['hide'] ?? null) ? $layout['hide'] : [];

        $out = ['columns' => [], 'hide' => []];
        foreach ($breakpoints as $bp) {
            if (isset($columns[$bp]) && $columns[$bp] !== '') {
                $out['columns'][$bp] = max(1, min(6, (int) $columns[$bp]));
            }
            $out['hide'][$bp] = ! empty($hide[$bp]);
        }

        return $out;
    }

    /**
     * Normalize admission form field configuration.
     * Supports both:
     * - New format: ['field_key' => ['enabled' => true, 'label' => '...', 'required' => false], ...]
     * - Old format: 'hidden' => 'field1,field2,field3' (comma-separated string)
     *
     * @param  array|string|null  $fields
     * @return array<string, array{enabled: bool, label: string, required: bool}>
     */
    private function normalizeAdmissionFields($fields): array
    {
        $defaults = [
            'last_name' => ['label' => 'Last name',          'required' => false],
            'blood_group' => ['label' => 'Blood group',        'required' => false],
            'student_phone' => ['label' => 'Student phone',      'required' => false],
            'photo' => ['label' => 'Student photo',      'required' => false],
            'guardian' => ['label' => 'Guardian information', 'required' => false],
            'permanent_address' => ['label' => 'Permanent address',  'required' => false],
            'notes' => ['label' => 'Notes',              'required' => false],
        ];

        // Handle old "hidden" format (string of comma-separated field keys)
        if (is_string($fields)) {
            $hidden = array_filter(array_map('trim', explode(',', $fields)));
            $normalized = [];
            foreach ($defaults as $key => $def) {
                $normalized[$key] = [
                    'enabled' => ! in_array($key, $hidden, true),
                    'label' => $def['label'],
                    'required' => $def['required'],
                ];
            }

            return $normalized;
        }

        // New format: array of field configs
        if (is_array($fields)) {
            $normalized = [];
            foreach ($defaults as $key => $def) {
                $cfg = $fields[$key] ?? [];
                $normalized[$key] = [
                    'enabled' => (bool) ($cfg['enabled'] ?? true),
                    'label' => $cfg['label'] ?? $def['label'],
                    'required' => (bool) ($cfg['required'] ?? $def['required']),
                ];
            }
            // Also include any custom fields
            foreach ($fields as $key => $cfg) {
                if (! array_key_exists($key, $defaults)) {
                    $normalized[$key] = [
                        'enabled' => (bool) ($cfg['enabled'] ?? true),
                        'label' => $cfg['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                        'required' => (bool) ($cfg['required'] ?? false),
                        'type' => $cfg['type'] ?? 'text',
                    ];
                }
            }

            return $normalized;
        }

        // Default: all enabled with defaults
        return array_map(fn ($def) => ['enabled' => true] + $def, $defaults);
    }

    /**
     * Prepare admission form field data for Blade template.
     * Returns a flat array with all field info needed for rendering.
     *
     * @param  array|string|null  $fields
     * @return array<string, mixed>
     */
    private function prepareAdmissionFormFields($fields): array
    {
        $normalized = $this->normalizeAdmissionFields($fields);

        $standardKeys = ['last_name', 'blood_group', 'student_phone', 'photo', 'guardian', 'permanent_address', 'notes'];
        $customFields = [];

        foreach ($normalized as $key => $cfg) {
            if (! in_array($key, $standardKeys, true)) {
                $customFields[$key] = [
                    'enabled' => (bool) ($cfg['enabled'] ?? true),
                    'label' => $cfg['label'] ?? ucfirst(str_replace('_', ' ', $key)),
                    'required' => (bool) ($cfg['required'] ?? false),
                    'type' => $cfg['type'] ?? 'text',
                    'options' => is_array($cfg['options'] ?? null) ? $cfg['options'] : (is_string($cfg['options'] ?? null) ? array_map('trim', explode(',', $cfg['options'])) : []),
                ];
            }
        }

        return [
            'standard' => array_intersect_key($normalized, array_flip($standardKeys)),
            'custom' => $customFields,
            'show' => fn ($key) => ! empty($normalized[$key]['enabled']),
            'getLabel' => fn ($key, $default) => $normalized[$key]['label'] ?? $default,
            'isRequired' => fn ($key) => ! empty($normalized[$key]['required']),
        ];
    }

    /** The page whose layout should drive the homepage, if any. */
    public function homepage(int $schoolId): ?Page
    {
        return Page::forSchool($schoolId)->published()->where('is_homepage', true)
            ->with('publishedLayout')->first();
    }

    /** How long a rendered page's live-resolved block data (notices/stats/staff) may lag reality. */
    private const CACHE_TTL = 300;

    /**
     * Cached counterpart to buildView() for a REAL published page — used by
     * the public PageController::show() and HomeController::index(), never
     * by the admin live-preview endpoints (preview()/previewBlock() call
     * buildView()/buildViewFromBlocks() directly, always uncached, since
     * they must reflect the editor's current unsaved form state exactly).
     *
     * Returns null when the page has no published layout yet — callers
     * decide what that means for them (PageController::show() still renders
     * an empty page; HomeController falls back to the default landing page).
     *
     * Deliberately keyed by the published PageLayout's own id, not the page
     * id: every publish() creates a brand-new PageLayout row (layouts are
     * versioned, see PageService/CLAUDE.md), so a fresh publish is
     * automatically a fresh cache key — no explicit flush-on-save wiring
     * needed, unlike the tag-flushing Observer pattern most other Repository
     * caches in this codebase use. The one staleness window this can't close
     * is a dynamic block's live data (notices/stats/staff counts) changing
     * without a new publish — bounded by CACHE_TTL rather than making
     * Announcement/Staff/etc. aware this cache exists.
     *
     * @return array{template: string, blocks: array, sidebar: array}|null
     */
    public function renderPage(Page $page): ?array
    {
        $layout = $page->publishedLayout->first();
        if (! $layout) {
            return null;
        }

        return Cache::tags(['pageview'])->remember(
            "pageview:layout:{$layout->id}",
            self::CACHE_TTL,
            fn () => $this->buildView($page->school_id, $layout->layout_json),
        );
    }

    /**
     * Render-ready structure: normalized template + each block paired with its
     * resolved live data under 'd', ready for the Blade partials to consume.
     * 'style'/'layout' pass through unresolved — they're presentation-only and
     * never touch live module data — for BlockPresentation to turn into markup.
     *
     * @param  array<string, mixed>|null  $layout
     * @return array{template: string, blocks: array<int, array{type: string, d: array, style: array, layout: array}>, sidebar: array<int, array{type: string, d: array, style: array, layout: array}>}
     */
    public function buildView(int $schoolId, ?array $layout): array
    {
        $norm = $this->normalize($layout);

        return $this->buildViewFromBlocks($schoolId, $norm['template'], $norm['blocks'], $norm['sidebar']);
    }

    /**
     * Same render-ready shape as buildView(), but from an already-normalized
     * blocks/sidebar array (each block already {type,data,style,layout}) held
     * in memory instead of loaded from a page's stored layout_json. This is
     * the seam the admin live-preview endpoint uses: it runs the admin's
     * posted (unsaved) form data through the exact same
     * live-data-resolution + presentation pipeline as a saved, published page,
     * so the preview can never drift from what the real site would render.
     *
     * @param  array<int, array{type: string, data: array, style: array, layout: array}>  $blocks
     * @param  array<int, array{type: string, data: array, style: array, layout: array}>  $sidebar
     * @return array{template: string, blocks: array<int, array{type: string, d: array, style: array, layout: array}>, sidebar: array<int, array{type: string, d: array, style: array, layout: array}>}
     */
    public function buildViewFromBlocks(int $schoolId, string $template, array $blocks, array $sidebar): array
    {
        $map = fn (array $b): array => [
            'type' => $b['type'],
            'd' => $this->resolveBlockData($schoolId, $b),
            'style' => $b['style'] ?? [],
            'layout' => $b['layout'] ?? [],
        ];

        $template = $template === 'sidebar' ? 'sidebar' : 'full';

        return [
            'template' => $template,
            'blocks' => array_map($map, $blocks),
            'sidebar' => $template === 'sidebar' ? array_map($map, $sidebar) : [],
        ];
    }
}
