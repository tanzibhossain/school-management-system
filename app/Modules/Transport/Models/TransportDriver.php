<?php

namespace App\Modules\Transport\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransportDriver extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'phone',
        'license_no',
        'status',
    ];

    /** @param Builder<TransportDriver> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<TransportDriver> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
