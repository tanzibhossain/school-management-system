<?php

namespace App\Modules\Payment\Services;

use App\Modules\FeeItem\Models\FeeDiscount;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\Payment\Events\InvoiceCancelled;
use App\Modules\Payment\Events\InvoiceGenerated;
use App\Modules\Payment\Events\InvoicePaid;
use App\Modules\Payment\Events\InvoiceWaived;
use App\Modules\Payment\Models\Invoice;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\StudentAcademic;
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

            // Pull active fee items for this class + year. Transport fee items
            // (transport_route_id set, class_id null) must NOT leak to the whole
            // class — they apply only to students actively assigned to that route.
            $feeItems = FeeItem::where('school_id', $schoolId)
                ->where('academic_year_id', $yearId)
                ->where('is_active', true)
                ->where(function ($q) use ($classId, $studentId): void {
                    $q->where(function ($normal) use ($classId): void {
                        $normal->whereNull('transport_route_id')
                            ->where(function ($c) use ($classId): void {
                                $c->whereNull('class_id')->orWhere('class_id', $classId);
                            });
                    })->orWhereIn('transport_route_id', function ($sub) use ($studentId): void {
                        $sub->select('transport_route_id')
                            ->from('student_transport_assignments')
                            ->where('student_id', $studentId)
                            ->where('status', 'active');
                    });
                })
                ->get();

            if ($feeItems->isEmpty()) {
                throw new RuntimeException('No active fee items found for this class and year.');
            }

            $discount = $discountId ? FeeDiscount::find($discountId) : null;
            $totalDue = 0.0;
            $lineItems = [];

            foreach ($feeItems as $item) {
                $discountAmt = $discount ? $discount->calculate((float) $item->amount) : 0.0;
                $netAmount = round((float) $item->amount - $discountAmt, 2);
                $totalDue += $netAmount;

                $lineItems[] = [
                    'fee_item_id' => $item->id,
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'discount_id' => $discountId,
                    'discount_amount' => $discountAmt,
                    'net_amount' => $netAmount,
                ];
            }

            // Auto-apply available student credit
            $creditBalance = $this->creditService->balance($schoolId, $studentId);
            $creditToApply = min($creditBalance, $totalDue);
            $amountDue = round($totalDue - $creditToApply, 2);

            $invoice = Invoice::create([
                'school_id' => $schoolId,
                'invoice_number' => $invoiceNumber,
                'student_id' => $studentId,
                'academic_year_id' => $yearId,
                'month' => $month,
                'amount_due' => $amountDue,
                'currency' => School::whereKey($schoolId)->value('currency') ?? 'USD',
                'amount_paid' => 0,
                'credit_applied' => $creditToApply,
                'status' => $amountDue == 0 ? 'paid' : 'unpaid',
                'due_date' => $dueDate,
                'issued_by' => $issuedBy,
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
     * Bulk invoice generation for every active student currently enrolled in a
     * class for the given year. Class membership lives on student_academics —
     * `students` has no class_id column (this method used to query one, so the
     * API's bulk path always failed). Students who already have an open
     * (unpaid/partial) invoice for the period are skipped; a student with no
     * applicable fee items is counted as failed instead of aborting the batch.
     *
     * @return array{generated: int, skipped: int, failed: int}
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
        $studentIds = StudentAcademic::where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('academic_year_id', $yearId)
            ->where('is_current', true)
            ->whereHas('student', fn ($q) => $q->where('status', 'active')->where('is_trash', false))
            ->pluck('student_id');

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($studentIds as $studentId) {
            $open = Invoice::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $yearId)
                ->where('month', $month)
                ->whereIn('status', ['unpaid', 'partial'])
                ->exists();

            if ($open) {
                $skipped++;

                continue;
            }

            try {
                $this->generate($schoolId, $yearId, $month, (int) $studentId, $classId, $discountId, $dueDate, $issuedBy);
                $generated++;
            } catch (RuntimeException) {
                $failed++; // no applicable fee items for this student
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'failed' => $failed];
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
