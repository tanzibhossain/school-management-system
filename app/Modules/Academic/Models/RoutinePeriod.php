<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RoutinePeriod extends Model
{
    protected $fillable = ['school_id', 'name', 'start_time', 'end_time', 'is_trash'];

    // Time columns stored as HH:MM:SS — returned as plain strings
    protected $casts = [
        'start_time' => 'string',
        'end_time'   => 'string',
        'is_trash'   => 'boolean',
    ];

    /** @param  Builder<RoutinePeriod>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }

    /** @param  Builder<RoutinePeriod>  $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
