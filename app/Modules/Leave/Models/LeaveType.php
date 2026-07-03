<?php

namespace App\Modules\Leave\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    public const APPLIES_TO = ['student', 'staff', 'both'];

    protected $fillable = [
        'school_id',
        'name',
        'applies_to',
        'max_days_per_year',
        'requires_attachment',
        'is_paid',
        'is_active',
    ];

    protected $casts = [
        'max_days_per_year' => 'integer',
        'requires_attachment' => 'boolean',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Mirror DB-level defaults
    protected $attributes = [
        'applies_to' => 'both',
        'requires_attachment' => false,
        'is_active' => true,
    ];

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  Builder  $query */
    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    /** Types usable by a 'student' or 'staff' person — 'both' always included. */
    /** @param  Builder  $query */
    public function scopeApplicableTo($query, string $person): void
    {
        $query->whereIn('applies_to', [$person, 'both']);
    }
}
