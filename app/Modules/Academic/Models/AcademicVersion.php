<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AcademicVersion extends Model
{
    protected $table = 'versions';

    protected $fillable = ['school_id', 'name', 'is_trash'];

    protected $casts = ['is_trash' => 'boolean'];

    /** @param  Builder<AcademicVersion>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
