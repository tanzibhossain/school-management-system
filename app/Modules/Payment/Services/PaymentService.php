<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Events\InvoicePaid;
use App\Modules\Payment\Events\OverpaymentCredited;
use App\Modules\Payment\Events\PaymentRecorded;
use App\Modules\Payment\Gateways\BkashGateway;
use App\Modules\Payment\Gateways\SslcommerzGateway;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PaymentNumberGeneratorService $numberGenerator,
        private readonly CreditService $creditService,
    ) {}

    // ── Manual payments (cash / cheque / bank_transfer / waiver) ─────────────

    /**
     * Record a manual payment. All financial writes in a single transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordManual(Invoice $invoice, array $data, int $collectedBy): Payment
    {
        if (in_array($invoice->status, ['paid', 'cancelled', 'waived'])) {
            throw new RuntimeException("Invoice is already {$invoice->status}.");
        }

        return DB::transaction(function () use ($invoice, $data, $collectedBy): Payment {
            $receiptNumber = $this->numberGenerator->nextReceiptNumber($invoice->school_id);

            $payment = Payment::create(array_merge($data, [
                'school_id'     => $invoice->school_id,
                'receipt_number'=> $receiptNumber,
                'invoice_id'    => $invoice->id,
                'student_id'    => $invoice->student_id,
                'collected_by'  => $collectedBy,
                'paid_at'       => now(),
                // Cheque defaults
                'cheque_status' => isset($data['cheque_number']) ? 'submitted' : null,
                'gateway_status'=> null,
            ]));

            $this->updateInvoiceAfterPayment($invoice, $payment);

            event(new PaymentRecorded($payment));

            return $payment->load('invoice');
        });
    }

    // ── bKash gateway ────────────────────────────────────────────────────────

    /**
     * Initiate a bKash payment for an invoice.
     *
     * @return array{ paymentID: string, bkashURL: string }
     */
    public function initiateBkash(Invoice $invoice, string $callbackUrl): array
    {
        $config  = $this->requireConfig($invoice->school_id);
        $gateway = new BkashGateway($config);
        $token   = $gateway->grantToken();

        $result = $gateway->createPayment(
            $token,
            $invoice->invoice_number,
            $invoice->remainingAmount(),
            $callbackUrl,
            $invoice->student_id,
        );

        // Cache the mapping so the callback controller can resolve school + invoice
        Cache::put(
            "bkash_payment:{$result['paymentID']}",
            ['school_id' => $invoice->school_id, 'invoice_id' => $invoice->id],
            now()->addHour(),
        );

        return $result;
    }

    /**
     * Execute a bKash payment after the customer completes on the bKash app.
     * invoiceId and schoolId are resolved from Redis cache by the callback controller.
     */
    public function executeBkash(string $paymentId, int $invoiceId, int $schoolId): Payment
    {
        $config  = $this->requireConfig($schoolId);
        $gateway = new BkashGateway($config);
        $token   = $gateway->grantToken();
        $result  = $gateway->executePayment($token, $paymentId);

        // If executePayment returns empty/null, fall back to queryPayment (per official library pattern)
        if (empty($result)) {
            $result = $gateway->queryPayment($token, $paymentId);
        }

        // Check both statusCode and transactionStatus (belt-and-suspenders per bKash docs)
        if (($result['statusCode'] ?? '') !== '0000' || ($result['transactionStatus'] ?? '') !== 'Completed') {
            throw new RuntimeException('bKash payment not completed: ' . ($result['statusMessage'] ?? 'unknown'));
        }

        $invoice = Invoice::findOrFail($invoiceId);

        return DB::transaction(function () use ($invoice, $result, $paymentId, $schoolId): Payment {
            $receiptNumber = $this->numberGenerator->nextReceiptNumber($schoolId);

            $payment = Payment::create([
                'school_id'          => $schoolId,
                'receipt_number'     => $receiptNumber,
                'invoice_id'         => $invoice->id,
                'student_id'         => $invoice->student_id,
                'amount'             => $result['amount'],
                'method'             => 'bkash',
                'transaction_ref'    => $result['trxID'],       // bank transaction ID
                'gateway_payment_id' => $paymentId,             // bKash paymentID — required for refunds
                'gateway_status'     => 'success',
                'collected_by'       => $invoice->issued_by,
                'paid_at'            => now(),
            ]);

            $this->updateInvoiceAfterPayment($invoice, $payment);
            Cache::forget("bkash_payment:{$paymentId}");
            event(new PaymentRecorded($payment));

            return $payment->load('invoice');
        });
    }

    // ── SSLCommerz gateway ───────────────────────────────────────────────────

    /**
     * Initiate an SSLCommerz session.
     *
     * @return array{ GatewayPageURL: string }
     */
    public function initiateSslcommerz(Invoice $invoice, string $successUrl, string $failUrl, string $cancelUrl, string $ipnUrl): array
    {
        $config  = $this->requireConfig($invoice->school_id);
        $gateway = new SslcommerzGateway($config);

        return $gateway->initSession(
            $invoice->invoice_number,
            $invoice->remainingAmount(),
            $invoice->student_id,
            $successUrl,
            $failUrl,
            $cancelUrl,
            $ipnUrl,
        );
    }

    /**
     * Verify and record an SSLCommerz payment from the IPN/success callback.
     * Invoice is resolved by the webhook controller from tran_id (= invoice_number).
     * Returns null if the payment was already recorded (idempotent).
     */
    public function verifySslcommerz(Invoice $invoice, string $valId): ?Payment
    {
        $schoolId = $invoice->school_id;
        $config   = $this->requireConfig($schoolId);
        $gateway  = new SslcommerzGateway($config);
        $result   = $gateway->validatePayment($valId);

        // SSLCommerz validation API returns 'VALID' or 'VALIDATED'
        if (! in_array($result['status'] ?? '', ['VALID', 'VALIDATED'], true)) {
            throw new RuntimeException('SSLCommerz payment validation failed.');
        }

        // S3: Verify tran_id matches our invoice (prevent replay)
        if (($result['tran_id'] ?? '') !== $invoice->invoice_number) {
            throw new RuntimeException('SSLCommerz tran_id does not match invoice.');
        }

        // S2: Verify validated amount covers the remaining balance
        $validatedAmount = (float) ($result['store_amount'] ?? $result['amount'] ?? 0);
        if ($validatedAmount < $invoice->remainingAmount()) {
            throw new RuntimeException('SSLCommerz validated amount is less than invoice remaining amount.');
        }

        // Idempotency — skip if already recorded for this val_id
        if (Payment::where('transaction_ref', $valId)->exists()) {
            return Payment::where('transaction_ref', $valId)->first();
        }

        return DB::transaction(function () use ($invoice, $result, $valId, $validatedAmount, $schoolId): Payment {
            $receiptNumber = $this->numberGenerator->nextReceiptNumber($schoolId);

            $payment = Payment::create([
                'school_id'          => $schoolId,
                'receipt_number'     => $receiptNumber,
                'invoice_id'         => $invoice->id,
                'student_id'         => $invoice->student_id,
                'amount'             => $validatedAmount,
                'method'             => 'sslcommerz',
                'transaction_ref'    => $valId,                      // val_id
                'gateway_payment_id' => $result['bank_tran_id'] ?? null, // required for refunds
                'gateway_status'     => 'success',
                'collected_by'       => $invoice->issued_by,
                'paid_at'            => now(),
            ]);

            $this->updateInvoiceAfterPayment($invoice, $payment);
            event(new PaymentRecorded($payment));

            return $payment->load('invoice');
        });
    }

    // ── Shared invoice update logic ──────────────────────────────────────────

    private function updateInvoiceAfterPayment(Invoice $invoice, Payment $payment): void
    {
        $invoice->refresh();
        $newPaid    = round((float) $invoice->amount_paid + (float) $payment->amount, 2);
        $amountDue  = (float) $invoice->amount_due;

        $status = match (true) {
            $newPaid >= $amountDue => 'paid',
            $newPaid > 0           => 'partial',
            default                => 'unpaid',
        };

        $invoice->update(['amount_paid' => $newPaid, 'status' => $status]);

        if ($status === 'paid') {
            event(new InvoicePaid($invoice->fresh()));

            // Handle overpayment → credit
            $overpayment = round($newPaid - $amountDue, 2);
            if ($overpayment > 0) {
                $this->creditService->credit(
                    $invoice->school_id,
                    $invoice->student_id,
                    $overpayment,
                    'payment',
                    $payment->id,
                    $payment->collected_by,
                    "Overpayment on invoice {$invoice->invoice_number}",
                );
                event(new OverpaymentCredited($payment, $overpayment));
            }
        }
    }

    private function requireConfig(int $schoolId): PaymentConfig
    {
        $config = PaymentConfig::where('school_id', $schoolId)->first();

        if (! $config) {
            throw new RuntimeException('Payment configuration not found for this school.');
        }

        return $config;
    }
}
