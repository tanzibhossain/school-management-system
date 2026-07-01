<?php

namespace App\Modules\FeeItem\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    protected $fillable = ['school_id', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(FeeItem::class, 'category_id');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
