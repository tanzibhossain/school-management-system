<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['school_id', 'name', 'sub_code', 'weight', 'is_trash'];

    protected $casts = [
        'weight'   => 'integer',
        'is_trash' => 'boolean',
    ];

    /** @return HasMany<SubjectRelation> */
    public function relations(): HasMany
    {
        return $this->hasMany(SubjectRelation::class, 'subject_id');
    }

    /** @param  Builder<Subject>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
