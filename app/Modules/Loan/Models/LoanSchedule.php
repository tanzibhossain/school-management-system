<?php

namespace App\Modules\Loan\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One installment of a StaffLoan's repayment schedule. is_paid/paid_amount/paid_at
 * are reserved for the future Payroll integration (deferred — see CLAUDE.md) and
 * are not written to by this module yet.
 */
class LoanSchedule extends Model
{
    protected $fillable = [
        'school_id',
        'staff_loan_id',
        'installment_number',
        'due_date',
        'amount',
        'is_paid',
        'paid_amount',
        'paid_at',
    ];

    protected $casts = [
        'installment_number' => 'integer',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Mirror DB-level default
    protected $attributes = [
        'is_paid' => false,
    ];

    /** @return BelongsTo<StaffLoan, LoanSchedule> */
    public function staffLoan(): BelongsTo
    {
        return $this->belongsTo(StaffLoan::class);
    }

    /** @param  Builder  $query */
    public function scopeForSchool($query, int $schoolId): void
    {
        $query->where('school_id', $schoolId);
    }
}
