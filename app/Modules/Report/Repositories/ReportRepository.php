<?php

namespace App\Modules\Report\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Cross-module aggregation queries over Payment's schema (invoices, payments,
 * refunds, student_credits, credit_transactions) joined to Student for
 * class/section context. Deliberately NOT extending BaseRepository — that
 * class is built around cache-aside reads for a single Eloquent model, and
 * this module has no model of its own to cache (see Report module's
 * docblock notes in SESSION_START.md for why steps 1/2/5 of the usual
 * 10-step pattern don't apply here).
 *
 * Built with DB::table() rather than Eloquent models: invoices/payments/etc.
 * store student_id/class references without DB-level FKs (cross-module, by
 * the same convention used everywhere else in this schema), so there are no
 * Eloquent relations to lean on here anyway — plain joins are the honest
 * representation of what's actually happening.
 */
class ReportRepository
{
    /**
     * Active (non-reversed) payments in a date range, joined to the invoice's
     * class/section (via the student's academic record for that invoice's
     * academic_year_id — NOT "current" class, so a report run later still
     * reflects the class the student was in when the fee was paid).
     *
     * @param  array{date_from: string, date_to: string, class_id?: int|null, section_id?: int|null, method?: string|null}  $filters
     */
    public function feeCollection(int $schoolId, array $filters): Collection
    {
        $query = DB::table('payments')
            ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
            ->join('students', 'students.id', '=', 'payments.student_id')
            ->leftJoin('student_academics', function ($join): void {
                $join->on('student_academics.student_id', '=', 'invoices.student_id')
                    ->on('student_academics.academic_year_id', '=', 'invoices.academic_year_id');
            })
            ->leftJoin('classes', 'classes.id', '=', 'student_academics.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'student_academics.section_id')
            ->where('payments.school_id', $schoolId)
            ->where('payments.is_reversed', false)
            ->whereBetween(DB::raw('date(payments.paid_at)'), [$filters['date_from'], $filters['date_to']]);

        if (! empty($filters['class_id'])) {
            $query->where('student_academics.class_id', $filters['class_id']);
        }
        if (! empty($filters['section_id'])) {
            $query->where('student_academics.section_id', $filters['section_id']);
        }
        if (! empty($filters['method'])) {
            $query->where('payments.method', $filters['method']);
        }

        return $query->select([
            'payments.id', 'payments.receipt_number', 'payments.amount', 'payments.currency',
            'payments.method', 'payments.paid_at',
            'students.id as student_id', 'students.name as student_name',
            'classes.name as class_name', 'sections.name as section_name',
        ])->orderBy('payments.paid_at')->get();
    }

    /**
     * Invoices currently unpaid/partial, with the student's remaining amount
     * (amount_due − amount_paid − credit_applied) and class/section context
     * for the invoice's own academic_year_id.
     *
     * @param  array{class_id?: int|null, section_id?: int|null, academic_year_id?: int|null}  $filters
     */
    public function outstandingDues(int $schoolId, array $filters): Collection
    {
        $query = DB::table('invoices')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('student_academics', function ($join): void {
                $join->on('student_academics.student_id', '=', 'invoices.student_id')
                    ->on('student_academics.academic_year_id', '=', 'invoices.academic_year_id');
            })
            ->leftJoin('classes', 'classes.id', '=', 'student_academics.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'student_academics.section_id')
            ->where('invoices.school_id', $schoolId)
            ->whereIn('invoices.status', ['unpaid', 'partial']);

        if (! empty($filters['class_id'])) {
            $query->where('student_academics.class_id', $filters['class_id']);
        }
        if (! empty($filters['section_id'])) {
            $query->where('student_academics.section_id', $filters['section_id']);
        }
        if (! empty($filters['academic_year_id'])) {
            $query->where('invoices.academic_year_id', $filters['academic_year_id']);
        }

        return $query->select([
            'invoices.id as invoice_id', 'invoices.invoice_number', 'invoices.amount_due',
            'invoices.amount_paid', 'invoices.credit_applied', 'invoices.currency',
            'invoices.due_date', 'invoices.status',
            'students.id as student_id', 'students.name as student_name',
            'classes.name as class_name', 'sections.name as section_name',
        ])->orderBy('invoices.due_date')->get();
    }

    /**
     * Raw rows for one student's invoices/payments/refunds/credit transactions
     * within an optional date window — merged and sorted into a single
     * timeline by ReportService::studentLedger(), not here (this stays a
     * plain data-access layer).
     *
     * @param  array{date_from?: string|null, date_to?: string|null}  $filters
     * @return array{invoices: Collection, payments: Collection, refunds: Collection, credits: Collection}
     */
    public function studentLedgerRows(int $schoolId, int $studentId, array $filters): array
    {
        $invoices = DB::table('invoices')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId);

        $payments = DB::table('payments')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('is_reversed', false);

        $refunds = DB::table('refunds')
            ->join('payments', 'payments.id', '=', 'refunds.payment_id')
            ->where('refunds.school_id', $schoolId)
            ->where('payments.student_id', $studentId)
            ->where('refunds.status', 'completed');

        $credits = DB::table('credit_transactions')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId);

        if (! empty($filters['date_from'])) {
            $invoices->whereDate('created_at', '>=', $filters['date_from']);
            $payments->whereDate('paid_at', '>=', $filters['date_from']);
            $refunds->whereDate('refunds.processed_at', '>=', $filters['date_from']);
            $credits->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $invoices->whereDate('created_at', '<=', $filters['date_to']);
            $payments->whereDate('paid_at', '<=', $filters['date_to']);
            $refunds->whereDate('refunds.processed_at', '<=', $filters['date_to']);
            $credits->whereDate('created_at', '<=', $filters['date_to']);
        }

        return [
            'invoices' => $invoices->select(['invoice_number', 'amount_due', 'currency', 'status', 'created_at'])->get(),
            'payments' => $payments->select(['receipt_number', 'amount', 'currency', 'method', 'paid_at'])->get(),
            'refunds' => $refunds->select(['refunds.net_refund', 'refunds.processed_at'])->get(),
            'credits' => $credits->select(['type', 'amount', 'note', 'created_at'])->get(),
        ];
    }

    public function currentCreditBalance(int $schoolId, int $studentId): float
    {
        $balance = DB::table('student_credits')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->value('balance');

        return (float) ($balance ?? 0);
    }

    /** Sum of remaining amounts on this student's currently unpaid/partial invoices. */
    public function currentOutstanding(int $schoolId, int $studentId): float
    {
        $rows = DB::table('invoices')
            ->where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->select(['amount_due', 'amount_paid', 'credit_applied'])
            ->get();

        return round((float) $rows->sum(fn ($row) => $row->amount_due - $row->amount_paid - $row->credit_applied), 2);
    }
}
