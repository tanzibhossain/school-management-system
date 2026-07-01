<?php

namespace App\Modules\Academic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = ['school_id', 'name', 'weight', 'is_trash'];

    protected $casts = [
        'weight'   => 'integer',
        'is_trash' => 'boolean',
    ];

    /** @return HasMany<Section> */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'class_id');
    }

    /** @return HasMany<SubjectRelation> */
    public function subjectRelations(): HasMany
    {
        return $this->hasMany(SubjectRelation::class, 'class_id');
    }

    /** @param  Builder<SchoolClass>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_trash', false);
    }
}
