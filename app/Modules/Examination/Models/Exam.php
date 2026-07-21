<?php

namespace App\Modules\Examination\Models;

use App\Modules\Academic\Models\SchoolClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'school_id',
        'exam_type_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'group_id',
        'version_id',
        'title',
        'start_date',
        'end_date',
        'status',
        'seating_strategy',
    ];

    // Eloquent doesn't auto-populate DB-level defaults after create().
    // These ensure the in-memory model reflects the correct defaults immediately.
    protected $attributes = [
        'status' => 'draft',
        'seating_strategy' => 'sequential',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    /** @return BelongsTo<ExamType, Exam> */
    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    /** @return BelongsTo<SchoolClass, Exam> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return HasMany<ExamSubject> */
    public function subjects(): HasMany
    {
        return $this->hasMany(ExamSubject::class)->orderBy('exam_date')->orderBy('start_time');
    }

    /** @return HasMany<ExamSeating> */
    public function seating(): HasMany
    {
        return $this->hasMany(ExamSeating::class)->orderBy('exam_roll');
    }

    // ── Computed attributes ────────────────────────────────────────────────────

    /**
     * True when the exam is published and today falls within its date range.
     * Derived — never stored; returned in the Resource as `is_ongoing`.
     */
    public function getIsOngoingAttribute(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }
        $today = now()->toDateString();

        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────

    /** @param Builder<Exam> $query */
    public function scopeForClass(Builder $query, int $classId, int $yearId): Builder
    {
        return $query->where('class_id', $classId)->where('academic_year_id', $yearId);
    }

    /** @param Builder<Exam> $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
