<?php

namespace App\Modules\LMS\Models;

use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    protected $table = 'lms_submissions';

    protected $fillable = [
        'school_id',
        'assignment_id',
        'student_id',
        'file_path',
        'submitted_at',
        'late_submission',
        'marks_awarded',
        'teacher_feedback',
        'graded_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'late_submission' => 'boolean',
        'marks_awarded' => 'integer',
        'graded_at' => 'datetime',
    ];

    // Mirror DB-level default (avoid null in the response before a fresh() refetch)
    protected $attributes = [
        'late_submission' => false,
    ];

    /** @return BelongsTo<School, Submission> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<Assignment, Submission> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** @return BelongsTo<Student, Submission> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return HasOne<SubmissionAiCheck> */
    public function aiCheck(): HasOne
    {
        return $this->hasOne(SubmissionAiCheck::class);
    }

    /** @param Builder<Submission> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function isGraded(): bool
    {
        return $this->graded_at !== null;
    }
}
