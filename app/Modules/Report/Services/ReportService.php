<?php

namespace App\Modules\Report\Services;

use App\Modules\Report\Repositories\ReportRepository;
use Illuminate\Support\Collection;

/**
 * Orchestrates ReportRepository's raw query results into the shaped arrays
 * the Resources return. No caching (per the "always live" decision) — these
 * read already-persisted, already-fast Payment data directly, and financial
 * reports going stale would be worse than the cost of recomputing them.
 */
class ReportService
{
    public function __construct(
        private readonly ReportRepository $repository,
    ) {}

    /**
     * @param  array{date_from: string, date_to: string, class_id?: int|null, section_id?: int|null, method?: string|null}  $filters
     * @return array{payments: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function feeCollection(int $schoolId, array $filters): array
    {
        $rows = $this->repository->feeCollection($schoolId, $filters);

        $payments = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'receipt_number' => $row->receipt_number,
            'student_id' => (int) $row->student_id,
            'student_name' => $row->student_name,
            'class_name' => $row->class_name,
            'section_name' => $row->section_name,
            'amount' => round((float) $row->amount, 2),
            'currency' => $row->currency,
            'method' => $row->method,
            'paid_at' => $row->paid_at,
        ])->values()->all();

        return [
            'payments' => $payments,
            'summary' => [
                'count' => $rows->count(),
                'totals_by_currency' => $this->sumByCurrency($rows, 'amount'),
                'totals_by_method' => $rows->groupBy('method')
                    ->map(fn (Collection $group) => round((float) $group->sum('amount'), 2))
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array{class_id?: int|null, section_id?: int|null, academic_year_id?: int|null}  $filters
     * @return array{students: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function outstandingDues(int $schoolId, array $filters): array
    {
        $rows = $this->repository->outstandingDues($schoolId, $filters);
        $remaining = fn ($row) => (float) $row->amount_due - (float) $row->amount_paid - (float) $row->credit_applied;

        $students = $rows->groupBy('student_id')->map(function (Collection $group) use ($remaining) {
            $first = $group->first();

            return [
                'student_id' => (int) $first->student_id,
                'student_name' => $first->student_name,
                'class_name' => $first->class_name,
                'section_name' => $first->section_name,
                'currency' => $first->currency,
                'invoice_count' => $group->count(),
                'oldest_due_date' => $group->min('due_date'),
                'total_due' => round((float) $group->sum($remaining), 2),
                'invoices' => $group->map(fn ($row) => [
                    'invoice_number' => $row->invoice_number,
                    'due_date' => $row->due_date,
                    'status' => $row->status,
                    'remaining' => round($remaining($row), 2),
                ])->values()->all(),
            ];
        })->values()->all();

        return [
            'students' => $students,
            'summary' => [
                'student_count' => count($students),
                'invoice_count' => $rows->count(),
                'totals_by_currency' => $rows->groupBy('currency')
                    ->map(fn (Collection $group) => round((float) $group->sum($remaining), 2))
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array{entries: array<int, array<string, mixed>>, summary: array<string, mixed>}
     */
    public function studentLedger(int $schoolId, int $studentId, array $filters): array
    {
        $rows = $this->repository->studentLedgerRows($schoolId, $studentId, $filters);

        $entries = collect();

        foreach ($rows['invoices'] as $invoice) {
            $entries->push([
                'date' => $invoice->created_at,
                'type' => 'invoice',
                'description' => "Invoice {$invoice->invoice_number}",
                'amount' => round((float) $invoice->amount_due, 2),
                'currency' => $invoice->currency,
            ]);
        }

        foreach ($rows['payments'] as $payment) {
            $entries->push([
                'date' => $payment->paid_at,
                'type' => 'payment',
                'description' => "Payment {$payment->receipt_number} ({$payment->method})",
                'amount' => round((float) $payment->amount, 2),
                'currency' => $payment->currency,
            ]);
        }

        foreach ($rows['refunds'] as $refund) {
            $entries->push([
                'date' => $refund->processed_at,
                'type' => 'refund',
                'description' => 'Refund',
                'amount' => round((float) $refund->net_refund, 2),
                'currency' => null,
            ]);
        }

        foreach ($rows['credits'] as $credit) {
            $entries->push([
                'date' => $credit->created_at,
                'type' => "credit_{$credit->type}",
                'description' => $credit->note ?: ucfirst($credit->type),
                'amount' => round((float) $credit->amount, 2),
                'currency' => null,
            ]);
        }

        return [
            'entries' => $entries->sortBy('date')->values()->all(),
            'summary' => [
                'total_invoiced' => round((float) $rows['invoices']->sum('amount_due'), 2),
                'total_paid' => round((float) $rows['payments']->sum('amount'), 2),
                'total_refunded' => round((float) $rows['refunds']->sum('net_refund'), 2),
                'current_outstanding' => $this->repository->currentOutstanding($schoolId, $studentId),
                'credit_balance' => $this->repository->currentCreditBalance($schoolId, $studentId),
            ],
        ];
    }

    /** @return array<string, float> */
    private function sumByCurrency(Collection $rows, string $field): array
    {
        return $rows->groupBy('currency')
            ->map(fn (Collection $group) => round((float) $group->sum($field), 2))
            ->all();
    }
}
