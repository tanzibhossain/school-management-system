<?php

namespace App\Modules\Loan\Services;

use InvalidArgumentException;

/**
 * Pure, unit-testable installment split for interest-free staff loans (no
 * amortization — this is deliberately simpler than the original DevPlan's
 * AmortizationCalculationEngine, since staff loans here carry no interest).
 */
class LoanScheduleCalculator
{
    /**
     * Split $amount evenly across $installmentCount installments. Division
     * remainders are absorbed entirely by the LAST installment, so the sum of
     * all installments always exactly equals $amount.
     *
     * @return array<int, array{installment_number: int, amount: float}>
     */
    public function calculateSchedule(float $amount, int $installmentCount): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Loan amount must be greater than zero.');
        }

        if ($installmentCount < 1) {
            throw new InvalidArgumentException('Installment count must be at least 1.');
        }

        $base = round($amount / $installmentCount, 2);
        $schedule = [];
        $allocated = 0.0;

        for ($number = 1; $number <= $installmentCount; $number++) {
            if ($number < $installmentCount) {
                $schedule[] = ['installment_number' => $number, 'amount' => $base];
                $allocated += $base;
            } else {
                $schedule[] = ['installment_number' => $number, 'amount' => round($amount - $allocated, 2)];
            }
        }

        return $schedule;
    }
}
