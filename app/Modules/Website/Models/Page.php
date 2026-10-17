<?php

namespace App\Modules\Website\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    public const STATUSES = ['draft', 'published'];

    protected $fillable = [
        'school_id',
        'slug',
        'title',
        'meta_title',
        'meta_desc',
        'og_image',
        'status',
        'is_homepage',
    ];

    protected $casts = [
        'is_homepage' => 'boolean',
    ];

    /** @return BelongsTo<School, $this> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Ordered newest-first — every save creates a NEW row, never an update
     * (see PageService), so "layouts()->first()" is relied on everywhere as
     * "the current latest revision" (edit()'s loaded layout, save()'s
     * optimistic-concurrency check, etc.). `created_at` is a plain
     * `TIMESTAMP` column (second precision, see the page_layouts
     * migration), so two saves inside the same wall-clock second tie on it
     * — an `id DESC` tiebreak is required for "latest" to be unambiguous;
     * without it, ties resolve to whatever order the storage engine
     * happens to return, which is not reliably insertion order. Found via
     * a real, reproducible PHPUnit failure
     * (PageBuilderTest::test_concurrent_save_keeps_both_revisions_and_warns_instead_of_overwriting),
     * not just reasoned about — two saves inside one test method easily
     * land in the same second.
     *
     * @return HasMany<PageLayout, $this>
     */
    public function layouts(): HasMany
    {
        return $this->hasMany(PageLayout::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * Same ordering as layouts(), scoped to one locale — docs/modules/
     * 30-multilingual-content-plan.md Phase 2. "Latest revision" is now
     * per-locale: each language has its own independent draft/publish
     * history, so admin edit()/history() need this instead of layouts()
     * whenever "latest" means "latest FOR THE LOCALE BEING EDITED".
     *
     * @return HasMany<PageLayout, $this>
     */
    public function layoutsForLocale(string $locale): HasMany
    {
        return $this->hasMany(PageLayout::class)->where('locale', $locale)
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    /**
     * NOT locale-scoped by itself — a page can have one published row PER
     * locale simultaneously now (Phase 2: publishing the English content
     * doesn't touch a Bangla draft, and vice versa). Callers that need "the
     * published layout for locale X" must add ->where('locale', ...)
     * themselves (see PageRenderService::publishedLayoutFor()) rather than
     * relying on ->first() here, which would return whichever locale's
     * published row the query happens to return first once more than one
     * exists.
     *
     * @return HasMany<PageLayout, $this>
     */
    public function publishedLayout(): HasMany
    {
        return $this->hasMany(PageLayout::class)->where('is_published', true);
    }

    /**
     * True if this page has a currently-PUBLISHED layout for $locale — what's
     * actually live on the public site for that language, not merely "a
     * draft revision exists" (see publishedLayout()'s own docblock: a page
     * can have one published row per locale simultaneously). Backs the
     * tick/cross translation-status columns on the admin Pages list.
     * Expects `publishedLayout` to be eager-loaded (PageController::index()
     * does this via `with('publishedLayout:id,page_id,locale')`) so this is
     * an in-memory check, not a query per row per language.
     */
    public function hasPublishedTranslation(string $locale): bool
    {
        return $this->publishedLayout->contains(fn (PageLayout $l) => $l->locale === $locale);
    }

    /** @param Builder<Page> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<Page> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
