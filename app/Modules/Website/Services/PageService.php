<?php

namespace App\Modules\Website\Services;

use App\Models\User;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\PageRedirect;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pages own their slug/SEO/status; the actual visual layout lives in
 * PageLayout (versioned — every save is a new row, never an update).
 */
class PageService
{
    /** Reserved paths that would collide with the API/admin app itself. */
    private const RESERVED_SLUGS = ['api', 'admin', 'login', 'dashboard', 'horizon', 'storage'];

    /** @param array<string, mixed> $data */
    public function create(int $schoolId, array $data): Page
    {
        $slug = $this->resolveSlug($schoolId, $data['slug'] ?? null, $data['title']);

        return Page::create(array_merge($data, [
            'school_id' => $schoolId,
            'slug' => $slug,
            'status' => $data['status'] ?? 'draft',
        ]));
    }

    /** @param array<string, mixed> $data */
    public function update(Page $page, array $data): Page
    {
        return DB::transaction(function () use ($page, $data): Page {
            $oldSlug = $page->slug;

            if (isset($data['slug']) && $data['slug'] !== $oldSlug) {
                $newSlug = $this->resolveSlug($page->school_id, $data['slug'], $data['title'] ?? $page->title, ignorePageId: $page->id);

                PageRedirect::create([
                    'school_id' => $page->school_id,
                    'old_slug' => $oldSlug,
                    'new_slug' => $newSlug,
                ]);

                $data['slug'] = $newSlug;
            }

            $page->update($data);

            return $page->fresh();
        });
    }

    /** Creates a new (draft) revision — never overwrites a prior one. */
    public function saveLayout(Page $page, array $layoutJson, ?User $user): PageLayout
    {
        return PageLayout::create([
            'school_id' => $page->school_id,
            'page_id' => $page->id,
            'layout_json' => $layoutJson,
            'is_published' => false,
            'created_by' => $user?->id,
        ]);
    }

    /** Publishes one revision (defaults to the latest) and unpublishes any other. */
    public function publish(Page $page, ?int $layoutId = null): Page
    {
        return DB::transaction(function () use ($page, $layoutId): Page {
            $target = $layoutId
                ? $page->layouts()->findOrFail($layoutId)
                : $page->layouts()->firstOrFail();

            $page->layouts()->where('is_published', true)->update(['is_published' => false]);
            $target->update(['is_published' => true, 'published_at' => now()]);

            $page->update(['status' => 'published']);

            return $page->fresh();
        });
    }

    /** Clones the page (new slug) and its latest layout — never the published-only one, so drafts carry over. */
    public function duplicate(Page $page): Page
    {
        return DB::transaction(function () use ($page): Page {
            $copySlug = $this->resolveSlug($page->school_id, "{$page->slug}-copy", $page->title);

            $copy = Page::create([
                'school_id' => $page->school_id,
                'slug' => $copySlug,
                'title' => "{$page->title} (Copy)",
                'meta_title' => $page->meta_title,
                'meta_desc' => $page->meta_desc,
                'og_image' => $page->og_image,
                'status' => 'draft',
                'is_homepage' => false,
            ]);

            $latest = $page->layouts()->first();
            if ($latest) {
                PageLayout::create([
                    'school_id' => $copy->school_id,
                    'page_id' => $copy->id,
                    'layout_json' => $latest->layout_json,
                    'is_published' => false,
                    'created_by' => $latest->created_by,
                ]);
            }

            return $copy;
        });
    }

    /** Restores an old revision by creating a NEW row copying it — history is never rewound or destroyed. */
    public function restore(Page $page, PageLayout $revision, ?User $user): PageLayout
    {
        return PageLayout::create([
            'school_id' => $page->school_id,
            'page_id' => $page->id,
            'layout_json' => $revision->layout_json,
            'is_published' => false,
            'created_by' => $user?->id,
        ]);
    }

    /** Keeps pages.is_homepage and site_settings.homepage_page_id in sync — the setting is the source of truth. */
    public function setHomepage(Page $page): Page
    {
        return DB::transaction(function () use ($page): Page {
            Page::forSchool($page->school_id)->where('is_homepage', true)->update(['is_homepage' => false]);
            $page->update(['is_homepage' => true]);

            SiteSetting::forSchool($page->school_id)->update(['homepage_page_id' => $page->id]);

            return $page->fresh();
        });
    }

    private function resolveSlug(int $schoolId, ?string $requested, string $title, ?int $ignorePageId = null): string
    {
        $base = Str::slug($requested ?: $title);
        $slug = $base;
        $suffix = 1;

        while ($this->slugTaken($schoolId, $slug, $ignorePageId) || in_array($slug, self::RESERVED_SLUGS, true)) {
            $suffix++;
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    private function slugTaken(int $schoolId, string $slug, ?int $ignorePageId): bool
    {
        return Page::forSchool($schoolId)
            ->where('slug', $slug)
            ->when($ignorePageId, fn ($q) => $q->where('id', '!=', $ignorePageId))
            ->exists();
    }
}
