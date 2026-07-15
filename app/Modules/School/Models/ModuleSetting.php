<?php

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleSetting extends Model
{
    protected $table = 'school_module_settings';

    public const MODULES = ['payroll', 'lms', 'library', 'transport', 'messaging'];

    /** Friendly label + description for each optional module. */
    public const META = [
        'payroll' => ['Payroll', 'Salary components, payroll runs, salary certificates.'],
        'lms' => ['LMS', 'Courses, lessons, assignments, AI submission checks.'],
        'library' => ['Library', 'Books, members, borrow/return workflow.'],
        'transport' => ['Transport', 'Routes, vehicles, drivers, student assignments.'],
        'messaging' => ['Messaging', 'In-app threaded messaging between staff and families.'],
    ];

    protected $fillable = [
        'school_id',
        'module',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    // Mirror DB-level default (avoid null in the response before a fresh() refetch)
    protected $attributes = [
        'is_enabled' => false,
    ];

    /** @return BelongsTo<School, ModuleSetting> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @param Builder<ModuleSetting> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
