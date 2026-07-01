<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AcademicGroup extends Model
{
    protected $table = 'groups';

    protected $fillable = ['school_id', 'name', 'is_trash'];

    protected $casts = ['is_trash' => 'boolean'];

    /** @param  Builder<AcademicGroup>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
