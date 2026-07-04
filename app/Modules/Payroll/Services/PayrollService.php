<?php

namespace App\Modules\Payroll\Services;

use App\Models\User;
use App\Modules\Loan\Models\LoanSchedule;
use App\Modules\Payroll\Models\PayrollEntry;
use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Repositories\PayrollRunRepository;
use App\Modules\Staff\Models\Staff;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Core monthly payroll cycle. Calculation is deliberately flat — component
 * sums only, no attendance proration (matches the DevPlan's calculateGrossAndNet
 * exactly) — plus one addition the DevPlan didn't cover: due, unpaid
 * LoanSchedule installments are pulled in as an extra deduction line and
 * marked paid on approval, fulfilling what Loan's own docblocks said they
 * were waiting for ("repayment tracking is deferred until Payroll exists").
 */
class PayrollService
{
    public function __construct(
        private readonly PayrollRunRepository $runs,
        private readonly StaffSalaryValueService $salaryValues,
    ) {}

    public function createRun(int $schoolId, int $month, int $year, ?string $notes): PayrollRun
    {
        if (PayrollRun::forSchool($schoolId)->where('month', $month)->where('year', $year)->exists()) {
            throw new UnprocessableEntityHttpException("A payroll run already exists for {$month}/{$year}.");
        }

        return PayrollRun::create([
            'school_id' => $schoolId,
            'month' => $month,
            'year' => $year,
            'notes' => $notes,
        ]);
    }

    /**
     * Idempotent — resubmitting (re-processing) a still-draft run wipes and
     * regenerates its entries, same "resubmitting never errors" idea Attendance
     * uses for its daily register.
     */
    public function processRun(int $schoolId, int $runId, User $user): PayrollRun
    {
        return DB::transaction(function () use ($schoolId, $runId, $user): PayrollRun {
            // Locked inside the transaction — two concurrent process/approve calls on the
            // same run must not both pass the status guard before either commits.
            $run = PayrollRun::forSchool($schoolId)->whereKey($runId)->lockForUpdate()->firstOrFail();
            $this->guardStatus($run, 'draft', 'Only a draft run can be processed.');

            $run->entries()->delete();

            $monthStart = Carbon::create($run->year, $run->month, 1)->startOfMonth();
            $monthEnd = (clone $monthStart)->endOfMonth();

            foreach (Staff::where('school_id', $schoolId)->active()->get() as $staff) {
                $calc = $this->salaryValues->calculateGrossAndNet($schoolId, $staff->id);
                $lines = $calc['lines'];
                $totalDeductions = $calc['deductions'];

                foreach ($this->dueLoanSchedules($schoolId, $staff->id, $monthStart, $monthEnd) as $schedule) {
                    $amount = (float) $schedule->amount;
                    $lines[] = [
                        'label' => 'Loan Repayment (installment #'.$schedule->installment_number.')',
                        'type' => 'loan_deduction',
                        'amount' => $amount,
                        'loan_schedule_id' => $schedule->id,
                    ];
                    $totalDeductions += $amount;
                }

                PayrollEntry::create([
                    'school_id' => $schoolId,
                    'payroll_run_id' => $run->id,
                    'staff_id' => $staff->id,
                    'gross_salary' => $calc['gross'],
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $calc['gross'] - $totalDeductions,
                    'breakdown' => $lines,
                ]);
            }

            $run->update(['processed_by' => $user->id, 'processed_at' => now()]);

            return $run->fresh('entries');
        });
    }

    /** Locks the run's numbers and marks every deducted loan installment paid. */
    public function approveRun(int $schoolId, int $runId, User $user): PayrollRun
    {
        return DB::transaction(function () use ($schoolId, $runId, $user): PayrollRun {
            $run = PayrollRun::forSchool($schoolId)->whereKey($runId)->lockForUpdate()->firstOrFail();
            $this->guardStatus($run, 'draft', 'Only a draft run can be approved.');

            if (! $run->processed_at) {
                throw new UnprocessableEntityHttpException('Process this run before approving it.');
            }

            foreach ($run->entries as $entry) {
                foreach ($entry->breakdown ?? [] as $line) {
                    if (($line['type'] ?? null) !== 'loan_deduction') {
                        continue;
                    }

                    LoanSchedule::whereKey($line['loan_schedule_id'])->update([
                        'is_paid' => true,
                        'paid_amount' => $line['amount'],
                        'paid_at' => now(),
                    ]);
                }
            }

            $run->update(['status' => 'approved', 'approved_by' => $user->id, 'approved_at' => now()]);

            return $run->fresh('entries');
        });
    }

    /** @return Collection<int, PayrollRun> */
    public function forSchool(int $schoolId): Collection
    {
        return $this->runs->forSchool($schoolId);
    }

    /** @return Collection<int, LoanSchedule> */
    private function dueLoanSchedules(int $schoolId, int $staffId, Carbon $monthStart, Carbon $monthEnd): Collection
    {
        return LoanSchedule::where('school_id', $schoolId)
            ->where('is_paid', false)
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->whereHas('staffLoan', fn ($q) => $q->where('staff_id', $staffId)->where('status', 'approved'))
            ->get();
    }

    private function guardStatus(PayrollRun $run, string $expected, string $message): void
    {
        if ($run->status !== $expected) {
            throw new UnprocessableEntityHttpException($message);
        }
    }
}
