<?php

namespace App\Modules\Attendance\Models;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    public const STATUSES = ['present', 'absent', 'late', 'half_day', 'leave'];

    /** Statuses that count as attended (late students were still in class). */
    public const PRESENT_STATUSES = ['present', 'late'];

    protected $fillable = [
        'school_id', 'student_id', 'class_id', 'section_id', 'academic_year_id',
        'date', 'status', 'note', 'recorded_by', 'edited_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeOnDate($query, string $date): void
    {
        $query->whereDate('date', $date);
    }
}
