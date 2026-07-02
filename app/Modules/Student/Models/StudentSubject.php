<?php

namespace App\Modules\Student\Models;

use App\Modules\Academic\Models\SubjectRelation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubject extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'subject_relation_id', 'academic_year_id', 'is_optional',
    ];

    protected $casts = [
        'is_optional' => 'boolean',
    ];

    // Mirror DB-level default
    protected $attributes = ['is_optional' => false];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subjectRelation(): BelongsTo
    {
        return $this->belongsTo(SubjectRelation::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
