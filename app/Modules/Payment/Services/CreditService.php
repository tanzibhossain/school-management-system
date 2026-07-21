<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\CreditTransaction;
use App\Modules\Payment\Models\StudentCredit;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditService
{
    /**
     * Add credit to a student's balance (e.g. overpayment).
     */
    public function credit(
        int $schoolId,
        int $studentId,
        float $amount,
        string $referenceType,
        int $referenceId,
        int $createdBy,
        ?string $note = null,
    ): void {
        DB::transaction(function () use ($schoolId, $studentId, $amount, $referenceType, $referenceId, $createdBy, $note): void {
            StudentCredit::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['school_id' => $schoolId, 'student_id' => $studentId],
                    ['balance' => 0],
                );

            StudentCredit::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->increment('balance', $amount);

            CreditTransaction::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'type' => 'credit',
                'amount' => $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        });
    }

    /**
     * Deduct from a student's credit balance (e.g. applied to invoice).
     *
     * @throws RuntimeException if balance would go negative.
     */
    public function debit(
        int $schoolId,
        int $studentId,
        float $amount,
        string $referenceType,
        int $referenceId,
        int $createdBy,
        ?string $note = null,
    ): void {
        DB::transaction(function () use ($schoolId, $studentId, $amount, $referenceType, $referenceId, $createdBy, $note): void {
            $credit = StudentCredit::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first();

            if (! $credit || (float) $credit->balance < $amount) {
                throw new RuntimeException('Insufficient credit balance.');
            }

            $credit->decrement('balance', $amount);

            CreditTransaction::create([
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'type' => 'debit',
                'amount' => $amount,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'created_by' => $createdBy,
            ]);
        });
    }

    public function balance(int $schoolId, int $studentId): float
    {
        $credit = StudentCredit::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->first();

        return $credit ? (float) $credit->balance : 0.0;
    }
}
