<?php

namespace App\Modules\Leave\Models;

use App\Models\User;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeaveRequest extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'school_id',
        'staff_id',
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
        'from_date'    => 'date',
        'to_date'      => 'date',
        'working_days' => 'integer',
        'approved_at'  => 'datetime',
    ];

    // Mirror DB-level default
    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<Staff, StaffLeaveRequest> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @return BelongsTo<LeaveType, StaffLeaveRequest> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /** @return BelongsTo<User, StaffLeaveRequest> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, StaffLeaveRequest> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder  $query */
    public function scopeStatus($query, string $status): void
    {
        $query->where('status', $status);
    }
}
