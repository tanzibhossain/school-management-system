<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AcademicYear extends Model
{
    protected $fillable = ['school_id', 'year', 'is_current', 'is_trash'];

    protected $casts = [
        'is_current' => 'boolean',
        'is_trash'   => 'boolean',
    ];

    /** @param  Builder<AcademicYear>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }

    /** @param  Builder<AcademicYear>  $query */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
