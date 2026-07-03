<?php

namespace App\Modules\Leave\Models;

use App\Models\User;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentLeaveRequest extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'school_id',
        'student_id',
        'class_id',
        'section_id',
        'academic_year_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'working_days',
        'reason',
        'attachment_path',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'working_days' => 'integer',
        'approved_at' => 'datetime',
    ];

    // Mirror DB-level default
    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<Student, StudentLeaveRequest> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<SchoolClass, StudentLeaveRequest> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /** @return BelongsTo<Section, StudentLeaveRequest> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /** @return BelongsTo<LeaveType, StudentLeaveRequest> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /** @return BelongsTo<User, StudentLeaveRequest> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, StudentLeaveRequest> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  Builder  $query */
    public function scopeStatus($query, string $status): void
    {
        $query->where('status', $status);
    }
}
