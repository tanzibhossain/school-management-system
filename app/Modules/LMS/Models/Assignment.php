<?php

namespace App\Modules\LMS\Models;

use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    protected $table = 'lms_assignments';

    protected $fillable = [
        'school_id',
        'course_id',
        'title',
        'instructions',
        'due_date',
        'max_marks',
        'allow_late_submission',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_marks' => 'integer',
        'allow_late_submission' => 'boolean',
    ];

    // Mirror DB-level default (avoid null in the response before a fresh() refetch)
    protected $attributes = [
        'allow_late_submission' => true,
    ];

    /** @return BelongsTo<School, Assignment> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<Course, Assignment> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<Submission> */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /** @param Builder<Assignment> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function isPastDue(): bool
    {
        return now()->greaterThan($this->due_date);
    }
}
