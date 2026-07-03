<?php

namespace App\Modules\Loan\Models;

use App\Models\User;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffLoan extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'school_id',
        'staff_id',
        'requested_amount',
        'installment_count',
        'reason',
        'start_date',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'installment_count' => 'integer',
        'start_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // Mirror DB-level default
    protected $attributes = [
        'status' => 'pending',
    ];

    /** @return BelongsTo<Staff, StaffLoan> */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /** @return HasMany<LoanSchedule> */
    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_number');
    }

    /** @return BelongsTo<User, StaffLoan> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, StaffLoan> */
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
