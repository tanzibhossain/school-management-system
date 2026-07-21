<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcademicShift extends Model
{
    protected $table = 'shifts';

    protected $fillable = ['school_id', 'name', 'is_trash'];

    protected $casts = ['is_trash' => 'boolean'];

    /** @param  Builder<AcademicShift>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
