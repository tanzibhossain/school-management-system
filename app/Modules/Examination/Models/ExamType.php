<?php

namespace App\Modules\Examination\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    protected $fillable = ['school_id', 'name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** @return HasMany<Exam> */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    /** @param Builder<ExamType> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
