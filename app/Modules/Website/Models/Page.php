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

    /** @return BelongsTo<School, Page> */
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
     * @return HasMany<PageLayout>
     */
    public function layouts(): HasMany
    {
        return $this->hasMany(PageLayout::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    /** @return HasMany<PageLayout> */
    public function publishedLayout(): HasMany
    {
        return $this->hasMany(PageLayout::class)->where('is_published', true);
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
