<?php

namespace App\Modules\Mark\Models;

use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mark extends Model
{
    protected $fillable = [
        'school_id', 'exam_id', 'student_id', 'mark_division_id',
        'marks_obtained', 'is_absent', 'grace_marks', 'grace_given_by',
        'entered_by', 'locked_at',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'grace_marks' => 'decimal:2',
        'is_absent' => 'boolean',
        'locked_at' => 'datetime',
    ];

    // Mirror DB-level defaults
    protected $attributes = [
        'is_absent' => false,
        'grace_marks' => 0,
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(MarkDivision::class, 'mark_division_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Effective marks: obtained + grace (grace kept separate for the audit trail). */
    public function effectiveMarks(): float
    {
        return $this->is_absent ? 0.0 : (float) $this->marks_obtained + (float) $this->grace_marks;
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
