<?php

namespace App\Http\Controllers\Admin\Website;

use App\Modules\School\Models\School;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\PageRenderService;
use App\Modules\Website\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Website page builder (Blade). Manages pages + their block layout via the
 * Website module's PageService (versioned layouts). Blocks are posted as plain
 * form arrays (blocks[i][type], blocks[i][data][...]) — no client JSON — and
 * normalised here into the layout_json the public renderer consumes.
 */
class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pages,
        private readonly PageRenderService $render,
    ) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.website.pages.index', [
            'pages' => Page::forSchool($schoolId)->withCount('layouts')->orderByDesc('is_homepage')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.website.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150'],
            'template' => ['required', 'in:full,sidebar'],
        ]);

        $page = $this->pages->create($schoolId, [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'status' => 'draft',
        ]);

        // Seed an empty layout with the chosen template.
        $this->pages->saveLayout($page, ['template' => $data['template'], 'blocks' => [], 'sidebar' => []], $request->user());

        return redirect()->route('admin.pages.edit', $page->id)->with('status', __('Page Created — Add Your Content.'));
    }

    public function edit(int $id): View
    {
        $schoolId = app('current_school_id');
        // .createdBy eager-loaded for the editor's in-sidebar History panel
        // (see history()/restore() below) — avoids an N+1 there.
        $page = Page::forSchool($schoolId)->with('layouts.createdBy')->findOrFail($id);
        $layout = $page->layouts->first();  // latest revision

        return view('admin.website.pages.edit', [
            'page' => $page,
            'view' => $this->layoutForEditor($layout?->layout_json),
            'blocks' => PageRenderService::BLOCKS,
            'sidebarBlocks' => PageRenderService::SIDEBAR_BLOCKS,
        ]);
    }

    /** Save meta + a new layout revision, and publish it when status = published. */
    public function save(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:draft,published'],
            'template' => ['required', 'in:full,sidebar'],
            'blocks' => ['nullable', 'array'],
            'sidebar' => ['nullable', 'array'],
        ]);

        $this->pages->update($page, [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $page->slug,
            'status' => $data['status'],
        ]);

        $layout = [
            'template' => $data['template'],
            'blocks' => $this->normalizeBlocks($request->input('blocks', []), PageRenderService::BLOCKS),
            'sidebar' => $data['template'] === 'sidebar'
                ? $this->normalizeBlocks($request->input('sidebar', []), PageRenderService::SIDEBAR_BLOCKS)
                : [],
        ];

        $revision = $this->pages->saveLayout($page->fresh(), $layout, $request->user());

        if ($data['status'] === 'published') {
            $this->pages->publish($page->fresh(), $revision->id);
        }

        return redirect()->route('admin.pages.edit', $page->id)->with('status', __('Page Saved.'));
    }

    /**
     * Render the page exactly as the public site would, from the editor's
     * current (unsaved) form state — no DB write. Posted blocks/sidebar go
     * through the same normalizeBlocks()/sanitizeStyle()/sanitizeLayout()
     * boundary as a real save, then PageRenderService::buildViewFromBlocks()
     * resolves live module data (notices/stats/staff) exactly like the public
     * PageController does, so the preview can never show something the saved
     * page wouldn't. Used by the editor's live-preview iframe (debounced,
     * re-POSTed on every change) — never cached, never persisted.
     */
    public function preview(Request $request, int $id): View
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);

        $template = $request->input('template') === 'sidebar' ? 'sidebar' : 'full';
        $blocks = $this->normalizeBlocks($request->input('blocks', []), PageRenderService::BLOCKS);
        $sidebar = $template === 'sidebar'
            ? $this->normalizeBlocks($request->input('sidebar', []), PageRenderService::SIDEBAR_BLOCKS)
            : [];

        return view('public.page', [
            'page' => $page,
            'view' => $this->render->buildViewFromBlocks($schoolId, $template, $blocks, $sidebar),
            'settings' => SiteSetting::forSchool($schoolId),
            'school' => School::current(),
        ]);
    }

    /**
     * Render exactly one block from posted (unsaved) field values — the
     * lightweight counterpart to preview(): used once the editor has an
     * initial full render in the iframe, so a plain field edit inside a
     * single block's Content/Style/Layout tabs can patch just that element
     * in place instead of reloading the whole iframe (see edit.blade.php's
     * scheduleBlockPreview()). Goes through the same normalizeBlocks()/
     * sanitizeStyle()/sanitizeLayout()/resolveBlockData() calls as a full
     * preview or a real save — never a second rendering path.
     */
    public function previewBlock(Request $request, int $id): Response
    {
        $schoolId = app('current_school_id');
        Page::forSchool($schoolId)->findOrFail($id); // scopes/authorizes this request to the school's own page

        $group = $request->input('group') === 'sidebar' ? 'sidebar' : 'blocks';
        $allowed = $group === 'sidebar' ? PageRenderService::SIDEBAR_BLOCKS : PageRenderService::BLOCKS;

        $blocks = $this->normalizeBlocks([$request->input('block', [])], $allowed);
        if ($blocks === []) {
            return response('', 204);
        }
        $block = $blocks[0];

        $html = view($group === 'sidebar' ? 'public.sidebar.render' : 'public.blocks.render', [
            'type' => $block['type'],
            'd' => $this->render->resolveBlockData($schoolId, $block),
            'style' => $block['style'],
            'layout' => $block['layout'],
            'contained' => $request->boolean('contained'),
        ])->render();

        return response($html);
    }

    public function setHomepage(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);
        $this->pages->setHomepage($page);

        return back()->with('status', "“{$page->title}” is now the homepage.");
    }

    /** List every saved revision of this page — every save is a kept row, never overwritten. */
    public function history(int $id): View
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->with('layouts.createdBy')->findOrFail($id);

        return view('admin.website.pages.history', ['page' => $page]);
    }

    /** Copy an old revision's layout into a brand-new (draft) row — history is never rewound or destroyed. */
    public function restore(Request $request, int $id, int $layoutId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);
        $revision = $page->layouts()->findOrFail($layoutId);

        $this->pages->restore($page, $revision, $request->user());

        return redirect()->route('admin.pages.edit', $page->id)
            ->with('status', __('Revision restored as a new draft — review and Save to publish it.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', __('Page Deleted.'));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** Turn stored array fields back into editable multiline strings for the form. */
    private function layoutForEditor(?array $layout): array
    {
        $reverse = function (array $blocks): array {
            return collect($blocks)->map(function ($b) {
                $data = is_array($b['data'] ?? null) ? $b['data'] : [];
                foreach (['images', 'videos'] as $f) {
                    if (isset($data[$f]) && is_array($data[$f])) {
                        $data[$f] = implode("\n", array_map(fn ($v) => is_array($v) ? ($v['url'] ?? '') : $v, $data[$f]));
                    }
                }
                if (isset($data['links']) && is_array($data['links'])) {
                    $data['links'] = implode("\n", array_map(fn ($l) => ($l['label'] ?? '').'|'.($l['url'] ?? ''), $data['links']));
                }
                if (isset($data['lines']) && is_array($data['lines'])) {
                    $data['lines'] = implode("\n", array_map(fn ($l) => is_array($l) ? (($l['label'] ?? '').'|'.($l['value'] ?? '')) : $l, $data['lines']));
                }

                return [
                    'type' => $b['type'] ?? '',
                    'data' => $data,
                    'style' => is_array($b['style'] ?? null) ? $b['style'] : [],
                    'layout' => PageRenderService::sanitizeLayout(is_array($b['layout'] ?? null) ? $b['layout'] : []),
                ];
            })->all();
        };

        return [
            'template' => ($layout['template'] ?? 'full') === 'sidebar' ? 'sidebar' : 'full',
            'blocks' => $reverse($layout['blocks'] ?? []),
            'sidebar' => $reverse($layout['sidebar'] ?? []),
        ];
    }

    /** @param array<string, string> $allowed */
    private function normalizeBlocks(array $raw, array $allowed): array
    {
        $out = [];
        foreach ($raw as $b) {
            $type = $b['type'] ?? null;
            if (! is_string($type) || ! array_key_exists($type, $allowed)) {
                continue;
            }
            $data = is_array($b['data'] ?? null) ? $b['data'] : [];

            foreach (['images', 'videos'] as $f) {
                if (isset($data[$f])) {
                    $data[$f] = $this->lines($data[$f]);
                }
            }
            if (isset($data['links'])) {
                $data['links'] = $this->pairs($data['links'], 'label', 'url');
            }
            if (isset($data['lines'])) {
                $data['lines'] = $this->pairs($data['lines'], 'label', 'value');
            }

            $data = array_filter($data, fn ($v) => $v !== null && $v !== '' && $v !== []);
            $style = PageRenderService::sanitizeStyle(is_array($b['style'] ?? null) ? $b['style'] : []);
            $layout = PageRenderService::sanitizeLayout(is_array($b['layout'] ?? null) ? $b['layout'] : []);

            $out[] = ['type' => $type, 'data' => $data, 'style' => $style, 'layout' => $layout];
        }

        return $out;
    }

    /** @return array<int, string> */
    private function lines(?string $s): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $s))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
    }

    /** @return array<int, array<string, string>> */
    private function pairs(?string $s, string $k1, string $k2): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $s))
            ->map(fn ($l) => trim($l))->filter()
            ->map(function ($l) use ($k1, $k2) {
                $p = explode('|', $l, 2);

                return [$k1 => trim($p[0]), $k2 => trim($p[1] ?? '')];
            })->values()->all();
    }
}
