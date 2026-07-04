<?php

namespace App\Modules\LMS\Models;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Subject;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $table = 'lms_courses';

    protected $fillable = [
        'school_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'title',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Mirror DB-level default (avoid null in the response before a fresh() refetch)
    protected $attributes = [
        'is_active' => true,
    ];

    /** @return BelongsTo<School, Course> */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /** @return BelongsTo<SchoolClass, Course> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Subject, Course> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Staff, Course> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'teacher_id');
    }

    /** @return HasMany<Lesson> */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    /** @return HasMany<Assignment> */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** @param Builder<Course> $query */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    /** @param Builder<Course> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
