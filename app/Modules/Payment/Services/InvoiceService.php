<?php

namespace App\Modules\Payment\Services;

use App\Modules\FeeItem\Models\FeeDiscount;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Events\InvoiceCancelled;
use App\Modules\Payment\Events\InvoiceGenerated;
use App\Modules\Payment\Events\InvoicePaid;
use App\Modules\Payment\Events\InvoiceWaived;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Student\Models\Student;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    public function __construct(
        private readonly PaymentNumberGeneratorService $numberGenerator,
        private readonly CreditService $creditService,
    ) {}

    /**
     * Generate a single invoice for a student.
     * Returns the existing unpaid/partial invoice if one already exists (no duplicate).
     */
    public function generate(
        int $schoolId,
        int $yearId,
        ?int $month,
        int $studentId,
        ?int $classId,
        ?int $discountId,
        string $dueDate,
        int $issuedBy,
    ): Invoice {
        // Duplicate guard — return existing open invoice
        $existing = Invoice::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->where('academic_year_id', $yearId)
            ->where('month', $month)
            ->whereIn('status', ['unpaid', 'partial'])
            ->first();

        if ($existing) {
            return $existing->load(['items']);
        }

        return DB::transaction(function () use ($schoolId, $yearId, $month, $studentId, $classId, $discountId, $dueDate, $issuedBy): Invoice {
            $invoiceNumber = $this->numberGenerator->nextInvoiceNumber($schoolId);

            // Pull active fee items for this class + year
            $feeItems = FeeItem::where('school_id', $schoolId)
                ->where('academic_year_id', $yearId)
                ->where('is_active', true)
                ->where(function ($q) use ($classId): void {
                    $q->whereNull('class_id')->orWhere('class_id', $classId);
                })
                ->get();

            if ($feeItems->isEmpty()) {
                throw new RuntimeException('No active fee items found for this class and year.');
            }

            $discount   = $discountId ? FeeDiscount::find($discountId) : null;
            $totalDue   = 0.0;
            $lineItems  = [];

            foreach ($feeItems as $item) {
                $discountAmt = $discount ? $discount->calculate((float) $item->amount) : 0.0;
                $netAmount   = round((float) $item->amount - $discountAmt, 2);
                $totalDue   += $netAmount;

                $lineItems[] = [
                    'fee_item_id'     => $item->id,
                    'name'            => $item->name,
                    'amount'          => $item->amount,
                    'discount_id'     => $discountId,
                    'discount_amount' => $discountAmt,
                    'net_amount'      => $netAmount,
                ];
            }

            // Auto-apply available student credit
            $creditBalance = $this->creditService->balance($schoolId, $studentId);
            $creditToApply = min($creditBalance, $totalDue);
            $amountDue     = round($totalDue - $creditToApply, 2);

            $invoice = Invoice::create([
                'school_id'        => $schoolId,
                'invoice_number'   => $invoiceNumber,
                'student_id'       => $studentId,
                'academic_year_id' => $yearId,
                'month'            => $month,
                'amount_due'       => $amountDue,
                'amount_paid'      => 0,
                'credit_applied'   => $creditToApply,
                'status'           => $amountDue == 0 ? 'paid' : 'unpaid',
                'due_date'         => $dueDate,
                'issued_by'        => $issuedBy,
            ]);

            foreach ($lineItems as $lineItem) {
                $invoice->items()->create($lineItem);
            }

            if ($creditToApply > 0) {
                $this->creditService->debit(
                    $schoolId, $studentId, $creditToApply,
                    'invoice', $invoice->id, $issuedBy,
                    "Auto-applied to invoice {$invoiceNumber}",
                );
            }

            if ($amountDue == 0) {
                event(new InvoicePaid($invoice));
            }

            event(new InvoiceGenerated($invoice));

            return $invoice->load(['items']);
        });
    }

    /**
     * Bulk invoice generation for all active students in a class.
     * Silently skips students who already have an open invoice for the period.
     *
     * @return array{ generated: int, skipped: int }
     */
    public function generateBulk(
        int $schoolId,
        int $yearId,
        ?int $month,
        int $classId,
        ?int $discountId,
        string $dueDate,
        int $issuedBy,
    ): array {
        $students = Student::where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('status', 'active')
            ->get(['id', 'class_id']);

        $generated = 0;
        $skipped   = 0;

        foreach ($students as $student) {
            $exists = Invoice::where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where('academic_year_id', $yearId)
                ->where('month', $month)
                ->whereIn('status', ['unpaid', 'partial'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $this->generate($schoolId, $yearId, $month, $student->id, $student->class_id, $discountId, $dueDate, $issuedBy);
            $generated++;
        }

        return ['generated' => $generated, 'skipped' => $skipped];
    }

    public function cancel(Invoice $invoice, string $note, int $cancelledBy): Invoice
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            throw new RuntimeException("Cannot cancel a {$invoice->status} invoice.");
        }

        $invoice->update(['status' => 'cancelled', 'note' => $note]);
        event(new InvoiceCancelled($invoice));

        return $invoice->fresh();
    }

    public function waive(Invoice $invoice, string $note, int $waivedBy): Invoice
    {
        if (in_array($invoice->status, ['paid', 'cancelled', 'waived'])) {
            throw new RuntimeException("Cannot waive a {$invoice->status} invoice.");
        }

        $invoice->update(['status' => 'waived', 'note' => $note]);
        event(new InvoiceWaived($invoice));

        return $invoice->fresh();
    }
}
