<?php

namespace App\Modules\Mark\Models;

use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'total_marks', 'total_possible',
        'percentage', 'grade', 'gpa', 'is_pass', 'merit_position',
        'subject_breakdown', 'is_locked', 'locked_by', 'calculated_at',
    ];

    protected $casts = [
        'total_marks'       => 'decimal:2',
        'total_possible'    => 'decimal:2',
        'percentage'        => 'decimal:2',
        'gpa'               => 'decimal:2',
        'is_pass'           => 'boolean',
        'subject_breakdown' => 'array',
        'is_locked'         => 'boolean',
        'calculated_at'     => 'datetime',
    ];

    // Mirror DB-level defaults
    protected $attributes = [
        'is_pass'   => false,
        'is_locked' => false,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
