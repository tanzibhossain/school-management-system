<?php

namespace App\Modules\Website\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageRedirect extends Model
{
    // created_at only — an immutable log entry, matches the migration exactly.
    const UPDATED_AT = null;

    protected $fillable = ['school_id', 'old_slug', 'new_slug'];

    /** @param Builder<PageRedirect> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
