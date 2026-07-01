<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Events\RefundCompleted;
use App\Modules\Payment\Events\RefundFailed;
use App\Modules\Payment\Events\RefundRequested;
use App\Modules\Payment\Gateways\BkashGateway;
use App\Modules\Payment\Gateways\SslcommerzGateway;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentConfig;
use App\Modules\Payment\Models\Refund;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefundService
{
    /**
     * Request a refund for a payment.
     * For gateway payments, immediately initiates the gateway refund API call.
     * For cash/bank, creates a pending refund for admin to process manually.
     */
    public function request(Payment $payment, float $amount, int $requestedBy, ?string $note = null): Refund
    {
        if ($payment->is_reversed) {
            throw new RuntimeException('Cannot refund a reversed payment.');
        }

        if (in_array($payment->method, ['bkash', 'sslcommerz']) && $payment->gateway_status !== 'success') {
            throw new RuntimeException('Gateway payment must be successful before refund.');
        }

        if ($amount > (float) $payment->amount) {
            throw new RuntimeException('Refund amount cannot exceed payment amount.');
        }

        return DB::transaction(function () use ($payment, $amount, $requestedBy, $note): Refund {
            $config         = PaymentConfig::where('school_id', $payment->school_id)->first();
            $processingFee  = $this->calculateFee($payment->method, $amount, $config);
            $netRefund      = round($amount - $processingFee, 2);

            $refund = Refund::create([
                'school_id'      => $payment->school_id,
                'payment_id'     => $payment->id,
                'amount'         => $amount,
                'processing_fee' => $processingFee,
                'net_refund'     => $netRefund,
                'method'         => $payment->method,
                'status'         => 'pending',
                'requested_by'   => $requestedBy,
                'note'           => $note,
            ]);

            event(new RefundRequested($refund));

            // Initiate gateway refund immediately
            if ($payment->method === 'bkash') {
                $this->initiateBkashRefund($refund, $payment, $config);
            } elseif ($payment->method === 'sslcommerz') {
                $this->initiateSslcommerzRefund($refund, $payment, $config);
            }

            return $refund->fresh();
        });
    }

    /**
     * Handle gateway callback for refund completion (success or failure).
     *
     * @param  array<string, mixed>  $response
     */
    public function handleGatewayCallback(Refund $refund, array $response, string $status): void
    {
        DB::transaction(function () use ($refund, $response, $status): void {
            $refund->update([
                'status'       => $status,
                'gateway_ref'  => $response['refundTrxID'] ?? $response['refund_ref_id'] ?? null,
                'processed_at' => $status === 'completed' ? now() : null,
            ]);

            if ($status === 'completed') {
                event(new RefundCompleted($refund->fresh()));
            } else {
                event(new RefundFailed($refund->fresh()));
            }
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function calculateFee(string $method, float $amount, ?PaymentConfig $config): float
    {
        if (! $config) {
            return 0.0;
        }

        return match ($method) {
            'bkash'       => round($amount * ((float) $config->bkash_fee_pct / 100), 2),
            'sslcommerz'  => round($amount * ((float) $config->sslcommerz_fee_pct / 100), 2),
            default       => 0.0,
        };
    }

    private function initiateBkashRefund(Refund $refund, Payment $payment, ?PaymentConfig $config): void
    {
        if (! $config) {
            return;
        }

        try {
            $gateway = new BkashGateway($config);
            $token   = $gateway->grantToken();
            $result  = $gateway->refund(
                $token,
                $payment->gateway_payment_id,   // bKash paymentID (from createPayment)
                $payment->transaction_ref,       // bKash trxID (from executePayment)
                $refund->amount,
                'Fee refund',
            );

            $status = ($result['statusCode'] ?? '') === '0000' ? 'completed' : 'failed';
            $refund->update([
                'status'       => $status,
                'gateway_ref'  => $result['refundTrxID'] ?? null,
                'processed_at' => $status === 'completed' ? now() : null,
            ]);
        } catch (\Throwable $e) {
            $refund->update(['status' => 'failed']);
        }
    }

    private function initiateSslcommerzRefund(Refund $refund, Payment $payment, ?PaymentConfig $config): void
    {
        if (! $config) {
            return;
        }

        try {
            $gateway = new SslcommerzGateway($config);
            $result  = $gateway->refund(
                $payment->gateway_payment_id,  // SSLCommerz bank_tran_id (from validation)
                $refund->amount,
                'Fee refund',
                (string) $refund->id,
            );

            $status = ($result['APIConnect'] ?? '') === 'DONE' ? 'processing' : 'failed';
            $refund->update(['status' => $status]);
        } catch (\Throwable $e) {
            $refund->update(['status' => 'failed']);
        }
    }
}
