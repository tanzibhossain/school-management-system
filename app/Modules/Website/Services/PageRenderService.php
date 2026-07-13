<?php

namespace App\Modules\Website\Services;

use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Website\Models\Page;

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
        'hero'          => 'Hero banner',
        'heading'       => 'Heading',
        'richtext'      => 'Rich text',
        'image'         => 'Image',
        'image_text'    => 'Image + text',
        'staff'         => 'Staff list',
        'notices'       => 'Notices',
        'stats'         => 'Statistics',
        'gallery_photo' => 'Photo gallery',
        'gallery_video' => 'Video gallery',
        'admission_form' => 'Admission form',
        'contact'       => 'Contact',
    ];

    /** Sidebar-only block types. */
    public const SIDEBAR_BLOCKS = [
        'quick_links'   => 'Quick links',
        'office_hours'  => 'Office hours',
        'contact_info'  => 'Contact info',
        'recent_notices' => 'Recent notices',
    ];

    public function __construct(private readonly PublicPortalService $portal) {}

    /**
     * Normalise a raw layout_json array into a safe, render-ready structure.
     *
     * @param  array<string, mixed>|null  $layout
     * @return array{template: string, blocks: array<int, array{type: string, data: array}>, sidebar: array<int, array{type: string, data: array}>}
     */
    public function normalize(?array $layout): array
    {
        $template = ($layout['template'] ?? 'full') === 'sidebar' ? 'sidebar' : 'full';

        return [
            'template' => $template,
            'blocks'   => $this->cleanBlocks($layout['blocks'] ?? [], self::BLOCKS),
            'sidebar'  => $template === 'sidebar'
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
    public function resolveBlockData(int $schoolId, array $block): array
    {
        $data = $block['data'] ?? [];

        return match ($block['type']) {
            'notices', 'recent_notices' => $data + ['notices' => $this->portal->notices($schoolId)],
            'stats' => $data + ['stats' => $this->portal->stats($schoolId)],
            'staff' => $data + ['members' => $this->staffFor($schoolId, $data)],
            'contact_info', 'contact' => $data + ['school' => School::find($schoolId)],
            default => $data,
        };
    }

    /**
     * Staff list honouring the block's category filter. Categories map to the
     * real staff data: "teachers"/"employees" via designation grouping is
     * school-defined, so we filter by designation_id/department_id when given,
     * else return all active staff.
     *
     * @param  array<string, mixed>  $data
     * @return \Illuminate\Support\Collection<int, Staff>
     */
    private function staffFor(int $schoolId, array $data): \Illuminate\Support\Collection
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
     * Drop any block whose type isn't in the allow-list and coerce data to array.
     *
     * @param  array<int, mixed>  $blocks
     * @param  array<string, string>  $allowed
     * @return array<int, array{type: string, data: array}>
     */
    private function cleanBlocks(array $blocks, array $allowed): array
    {
        $out = [];
        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            if (! is_string($type) || ! array_key_exists($type, $allowed)) {
                continue;
            }
            $out[] = ['type' => $type, 'data' => is_array($block['data'] ?? null) ? $block['data'] : []];
        }

        return $out;
    }

    /** The page whose layout should drive the homepage, if any. */
    public function homepage(int $schoolId): ?Page
    {
        return Page::forSchool($schoolId)->published()->where('is_homepage', true)
            ->with('publishedLayout')->first();
    }

    /**
     * Render-ready structure: normalized template + each block paired with its
     * resolved live data under 'd', ready for the Blade partials to consume.
     *
     * @param  array<string, mixed>|null  $layout
     * @return array{template: string, blocks: array<int, array{type: string, d: array}>, sidebar: array<int, array{type: string, d: array}>}
     */
    public function buildView(int $schoolId, ?array $layout): array
    {
        $norm = $this->normalize($layout);
        $map = fn (array $b): array => ['type' => $b['type'], 'd' => $this->resolveBlockData($schoolId, $b)];

        return [
            'template' => $norm['template'],
            'blocks'   => array_map($map, $norm['blocks']),
            'sidebar'  => array_map($map, $norm['sidebar']),
        ];
    }
}
