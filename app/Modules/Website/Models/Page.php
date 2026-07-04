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

    /** @return HasMany<PageLayout> */
    public function layouts(): HasMany
    {
        return $this->hasMany(PageLayout::class)->orderByDesc('created_at');
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
