<?php

namespace App\Http\Controllers\Admin\Website;

use App\Modules\Website\Models\Page;
use App\Modules\Website\Services\PageRenderService;
use App\Modules\Website\Services\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function __construct(private readonly PageService $pages) {}

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
            'title'    => ['required', 'string', 'max:150'],
            'slug'     => ['nullable', 'string', 'max:150'],
            'template' => ['required', 'in:full,sidebar'],
        ]);

        $page = $this->pages->create($schoolId, [
            'title'  => $data['title'],
            'slug'   => $data['slug'] ?? null,
            'status' => 'draft',
        ]);

        // Seed an empty layout with the chosen template.
        $this->pages->saveLayout($page, ['template' => $data['template'], 'blocks' => [], 'sidebar' => []], $request->user());

        return redirect()->route('admin.pages.edit', $page->id)->with('status', 'Page created — add your content.');
    }

    public function edit(int $id): View
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->with('layouts')->findOrFail($id);
        $layout = $page->layouts->first();  // latest revision

        return view('admin.website.pages.edit', [
            'page'   => $page,
            'view'   => $this->layoutForEditor($layout?->layout_json),
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
            'title'    => ['required', 'string', 'max:150'],
            'slug'     => ['nullable', 'string', 'max:150'],
            'status'   => ['required', 'in:draft,published'],
            'template' => ['required', 'in:full,sidebar'],
            'blocks'   => ['nullable', 'array'],
            'sidebar'  => ['nullable', 'array'],
        ]);

        $this->pages->update($page, [
            'title'  => $data['title'],
            'slug'   => $data['slug'] ?? $page->slug,
            'status' => $data['status'],
        ]);

        $layout = [
            'template' => $data['template'],
            'blocks'   => $this->normalizeBlocks($request->input('blocks', []), PageRenderService::BLOCKS),
            'sidebar'  => $data['template'] === 'sidebar'
                ? $this->normalizeBlocks($request->input('sidebar', []), PageRenderService::SIDEBAR_BLOCKS)
                : [],
        ];

        $revision = $this->pages->saveLayout($page->fresh(), $layout, $request->user());

        if ($data['status'] === 'published') {
            $this->pages->publish($page->fresh(), $revision->id);
        }

        return redirect()->route('admin.pages.edit', $page->id)->with('status', 'Page saved.');
    }

    public function setHomepage(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);
        $this->pages->setHomepage($page);

        return back()->with('status', "“{$page->title}” is now the homepage.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $page = Page::forSchool($schoolId)->findOrFail($id);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
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
                    $data['links'] = implode("\n", array_map(fn ($l) => ($l['label'] ?? '') . '|' . ($l['url'] ?? ''), $data['links']));
                }
                if (isset($data['lines']) && is_array($data['lines'])) {
                    $data['lines'] = implode("\n", array_map(fn ($l) => is_array($l) ? (($l['label'] ?? '') . '|' . ($l['value'] ?? '')) : $l, $data['lines']));
                }

                return ['type' => $b['type'] ?? '', 'data' => $data];
            })->all();
        };

        return [
            'template' => ($layout['template'] ?? 'full') === 'sidebar' ? 'sidebar' : 'full',
            'blocks'   => $reverse($layout['blocks'] ?? []),
            'sidebar'  => $reverse($layout['sidebar'] ?? []),
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
            $out[] = ['type' => $type, 'data' => $data];
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
