<?php

namespace App\Modules\Loan\Services;

use App\Models\User;
use App\Modules\Loan\Models\LoanSchedule;
use App\Modules\Loan\Models\StaffLoan;
use App\Modules\Staff\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff loans are interest-free advances (agreed 2026-07-03 — see CLAUDE.md).
 * Workflow mirrors Leave: submit -> admin/accountant approve or reject ->
 * approval generates the repayment schedule. Marking individual installments
 * paid is deferred until the Payroll module (#21) exists — LoanSchedule's
 * is_paid/paid_amount/paid_at columns are reserved for that, not written here.
 */
class StaffLoanService
{
    public function __construct(
        private readonly LoanScheduleCalculator $calculator,
    ) {}

    /**
     * @param  array{requested_amount: float, installment_count: int, reason: string, start_date: string}  $data
     */
    public function submit(int $schoolId, Staff $staff, array $data, User $requester): StaffLoan
    {
        return StaffLoan::create([
            'school_id' => $schoolId,
            'staff_id' => $staff->id,
            'requested_amount' => $data['requested_amount'],
            'installment_count' => $data['installment_count'],
            'reason' => $data['reason'],
            'start_date' => $data['start_date'],
            'requested_by' => $requester->id,
        ]);
    }

    /**
     * Approve — generates the LoanSchedule rows (one per installment, monthly
     * cadence from start_date). This is the "disbursement" moment; there is no
     * separate disburse step.
     */
    public function approve(StaffLoan $loan, User $approver): StaffLoan
    {
        $this->assertCanDecide($approver);

        return DB::transaction(function () use ($loan, $approver): StaffLoan {
            $locked = StaffLoan::whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Only a pending loan request can be approved.'],
                ]);
            }

            $locked->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $this->generateSchedule($locked);

            return $locked->fresh();
        });
    }

    public function reject(StaffLoan $loan, User $approver, ?string $reason = null): StaffLoan
    {
        $this->assertCanDecide($approver);

        if ($loan->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Only a pending loan request can be rejected.'],
            ]);
        }

        $loan->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $loan->fresh();
    }

    /**
     * Cancel — the requester may cancel while pending; admin/accountant may
     * cancel a pending OR approved loan. Cancelling an approved loan also
     * removes its (still unpaid) schedule — safe, because repayment tracking
     * isn't wired up yet, so no installment can already be marked paid.
     */
    public function cancel(StaffLoan $loan, User $user): StaffLoan
    {
        $isDecider = $user->tokenCan('admin:*') || $user->tokenCan('accountant:*');

        if (! $isDecider && ($loan->requested_by !== $user->id || $loan->status !== 'pending')) {
            throw new AuthorizationException(
                'Only the requester (while pending) or an admin/accountant may cancel this request.'
            );
        }

        if (! in_array($loan->status, ['pending', 'approved'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Only a pending or approved loan request can be cancelled.'],
            ]);
        }

        if ($loan->status === 'approved') {
            LoanSchedule::where('staff_loan_id', $loan->id)->delete();
        }

        $loan->update(['status' => 'cancelled']);

        return $loan->fresh();
    }

    private function generateSchedule(StaffLoan $loan): void
    {
        $installments = $this->calculator->calculateSchedule(
            (float) $loan->requested_amount,
            $loan->installment_count,
        );

        $dueDate = CarbonImmutable::parse($loan->start_date->toDateString());

        foreach ($installments as $installment) {
            LoanSchedule::create([
                'school_id' => $loan->school_id,
                'staff_loan_id' => $loan->id,
                'installment_number' => $installment['installment_number'],
                'due_date' => $dueDate->toDateString(),
                'amount' => $installment['amount'],
            ]);

            $dueDate = $dueDate->addMonthNoOverflow();
        }
    }

    private function assertCanDecide(User $user): void
    {
        if (! $user->tokenCan('admin:*') && ! $user->tokenCan('accountant:*')) {
            throw new AuthorizationException('Only an admin or accountant may decide staff loan requests.');
        }
    }
}
