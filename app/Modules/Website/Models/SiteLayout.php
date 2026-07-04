<?php

namespace App\Modules\Website\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteLayout extends Model
{
    public const TYPES = ['header', 'footer'];

    // created_at only — same versioning reasoning as PageLayout.
    const UPDATED_AT = null;

    protected $fillable = [
        'school_id',
        'type',
        'layout_json',
        'is_published',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'layout_json' => 'array',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /** @return BelongsTo<User, SiteLayout> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<SiteLayout> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<SiteLayout> $query */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
