<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoutineRoom extends Model
{
    protected $fillable = ['school_id', 'name', 'capacity', 'is_trash'];

    protected $casts = [
        'capacity' => 'integer',
        'is_trash' => 'boolean',
    ];

    /** @param  Builder<RoutineRoom>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }

    /** @param  Builder<RoutineRoom>  $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
