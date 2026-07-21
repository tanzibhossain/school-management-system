<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentType extends Model
{
    protected $table = 'student_types';

    protected $fillable = ['school_id', 'name', 'is_trash'];

    protected $casts = ['is_trash' => 'boolean'];

    /** @param  Builder<StudentType>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
