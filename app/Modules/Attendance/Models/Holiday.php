<?php

namespace App\Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public const TYPES = ['government', 'religious', 'school', 'closure'];

    protected $fillable = ['school_id', 'date', 'name', 'type', 'created_by'];

    protected $casts = [
        'date' => 'date',
    ];

    // Mirror DB-level default
    protected $attributes = ['type' => 'school'];

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  Builder  $query */
    public function scopeForYear($query, int $year): void
    {
        $query->whereYear('date', $year);
    }
}
