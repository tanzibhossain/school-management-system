<?php

namespace App\Modules\Staff\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAcademic extends Model
{
    protected $fillable = [
        'school_id',
        'staff_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'subject',
        'is_class_teacher',
    ];

    protected $casts = [
        'is_class_teacher' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    /** @return BelongsTo<Staff, StaffAcademic> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** @param Builder<StaffAcademic> $query */
    public function scopeForYear(Builder $query, int $yearId): Builder
    {
        return $query->where('academic_year_id', $yearId);
    }

    /** @param Builder<StaffAcademic> $query */
    public function scopeClassTeachers(Builder $query): Builder
    {
        return $query->where('is_class_teacher', true);
    }
}
