<?php

namespace App\Modules\Website\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    protected $fillable = ['school_id', 'name', 'thumbnail', 'layout_json'];

    protected $casts = [
        'layout_json' => 'array',
    ];

    /** Global starter templates (seeded, available to every school) + this school's own saved ones. */
    public function scopeAvailableTo(Builder $query, int $schoolId): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('school_id')->orWhere('school_id', $schoolId));
    }
}
