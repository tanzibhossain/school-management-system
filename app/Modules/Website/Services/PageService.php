<?php

namespace App\Modules\Website\Services;

use App\Models\User;
use App\Modules\Language\Models\Language;
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

    /**
     * Creates a new (draft) revision for one locale — never overwrites a
     * prior one. $locale is required (not defaulted) so every call site has
     * to be explicit about which language it's writing; $meta carries this
     * revision's own per-locale title/meta_title/meta_desc/og_image (see
     * docs/modules/30-multilingual-content-plan.md Phase 2 — every
     * PageLayout row now owns its own SEO meta, not just the shared `pages`
     * row).
     *
     * @param  array{title?: ?string, meta_title?: ?string, meta_desc?: ?string, og_image?: ?string}  $meta
     */
    public function saveLayout(Page $page, array $layoutJson, ?User $user, string $locale, array $meta = []): PageLayout
    {
        return PageLayout::create([
            'school_id' => $page->school_id,
            'page_id' => $page->id,
            'locale' => $locale,
            'title' => $meta['title'] ?? null,
            'meta_title' => $meta['meta_title'] ?? null,
            'meta_desc' => $meta['meta_desc'] ?? null,
            'og_image' => $meta['og_image'] ?? null,
            'layout_json' => $layoutJson,
            'is_published' => false,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Save the page editor's form for ONE locale (docs/modules/30-multilingual-
     * content-plan.md Phase 2). `slug`/`status` are site structure, shared by
     * every locale. `title`/`meta_title`/`meta_desc`/`og_image` are content:
     * they update the shared `pages` row (the default-locale seed) only when
     * editing the default locale, and always ride along on the new PageLayout
     * revision, so a fallback-rendered page and a real default-locale edit
     * carry consistent meta.
     *
     * Optimistic concurrency: if someone ELSE saved a newer revision of THIS
     * locale since the editor loaded ($knownLayoutId), both admins' work is
     * kept — the new revision is saved but not published, for a human to sort
     * out in History. Two admins translating different locales never conflict.
     *
     * @param  array<string, mixed>  $data  validated editor input
     * @param  array<string, mixed>  $layout  normalised layout_json
     * @return bool true when a conflict was detected (saved as an unpublished draft)
     */
    public function saveFromEditor(Page $page, string $locale, array $data, array $layout, ?User $user, ?int $knownLayoutId): bool
    {
        $meta = [
            'title' => $data['title'],
            'meta_title' => $data['meta_title'] ?? null,
            'meta_desc' => $data['meta_desc'] ?? null,
            'og_image' => $data['og_image'] ?? null,
        ];
        $pageUpdate = ['slug' => $data['slug'] ?? $page->slug, 'status' => $data['status']];
        if ($locale === Language::defaultCode()) {
            $pageUpdate += $meta;
        }
        $this->update($page, $pageUpdate);

        $priorLatest = $page->layoutsForLocale($locale)->first();
        $conflict = $knownLayoutId !== null && $priorLatest && $knownLayoutId !== $priorLatest->id;

        $revision = $this->saveLayout($page->fresh(), $layout, $user, $locale, $meta);

        if ($data['status'] === 'published' && ! $conflict) {
            $this->publish($page->fresh(), $revision->id, $locale);
        }

        return $conflict;
    }

    /**
     * Publishes one revision of ONE locale (defaults to that locale's
     * latest) and unpublishes any other revision of the SAME locale only —
     * each language's published state is independent (publishing the
     * English content doesn't touch a Bangla draft, and vice versa; see
     * Page::publishedLayout()'s docblock).
     */
    public function publish(Page $page, ?int $layoutId, string $locale): Page
    {
        return DB::transaction(function () use ($page, $layoutId, $locale): Page {
            $target = $layoutId
                ? $page->layoutsForLocale($locale)->findOrFail($layoutId)
                : $page->layoutsForLocale($locale)->firstOrFail();

            $page->layoutsForLocale($locale)->where('is_published', true)->update(['is_published' => false]);
            $target->update(['is_published' => true, 'published_at' => now()]);

            $page->update(['status' => 'published']);

            return $page->fresh();
        });
    }

    /**
     * Clones the page (new slug) and the DEFAULT language's latest layout —
     * never the published-only one, so drafts carry over. Deliberately only
     * the default locale: a duplicate is a brand-new page, translations
     * aren't assumed to still apply and are re-added per-locale afterward
     * the same way any other page's are.
     */
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

            $latest = $page->layoutsForLocale(Language::defaultCode())->first();
            if ($latest) {
                PageLayout::create([
                    'school_id' => $copy->school_id,
                    'page_id' => $copy->id,
                    'locale' => $latest->locale,
                    'title' => $latest->title,
                    'meta_title' => $latest->meta_title,
                    'meta_desc' => $latest->meta_desc,
                    'og_image' => $latest->og_image,
                    'layout_json' => $latest->layout_json,
                    'is_published' => false,
                    'created_by' => $latest->created_by,
                ]);
            }

            return $copy;
        });
    }

    /** Restores an old revision by creating a NEW row copying it (same locale + meta) — history is never rewound or destroyed. */
    public function restore(Page $page, PageLayout $revision, ?User $user): PageLayout
    {
        return PageLayout::create([
            'school_id' => $page->school_id,
            'page_id' => $page->id,
            'locale' => $revision->locale,
            'title' => $revision->title,
            'meta_title' => $revision->meta_title,
            'meta_desc' => $revision->meta_desc,
            'og_image' => $revision->og_image,
            'layout_json' => $revision->layout_json,
            'is_published' => false,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Seeds a locale's first draft by copying another locale's revision —
     * the admin editor's "Copy from default language" action. Creates a
     * normal new draft row (same versioning rules as any other save); the
     * admin still reviews/translates/Saves it like any other edit.
     */
    public function copyLayoutToLocale(Page $page, PageLayout $source, string $toLocale, ?User $user): PageLayout
    {
        return PageLayout::create([
            'school_id' => $page->school_id,
            'page_id' => $page->id,
            'locale' => $toLocale,
            'title' => $source->title,
            'meta_title' => $source->meta_title,
            'meta_desc' => $source->meta_desc,
            'og_image' => $source->og_image,
            'layout_json' => $source->layout_json,
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
