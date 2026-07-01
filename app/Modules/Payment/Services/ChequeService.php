<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Events\ChequeBounced;
use App\Modules\Payment\Events\ChequeCleared;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChequeService
{
    public function clear(Payment $payment): Payment
    {
        if ($payment->method !== 'cheque') {
            throw new RuntimeException('Payment is not a cheque.');
        }

        if ($payment->cheque_status !== 'submitted') {
            throw new RuntimeException("Cheque is already {$payment->cheque_status}.");
        }

        $payment->update(['cheque_status' => 'cleared']);
        event(new ChequeCleared($payment->fresh()));

        return $payment->fresh();
    }

    /**
     * Mark a cheque as bounced: reverse the payment, reopen the invoice,
     * and optionally add a bounce fee to the invoice amount_due.
     */
    public function bounce(Payment $payment, int $bouncedBy, ?float $customBounceFee = null): void
    {
        if ($payment->method !== 'cheque') {
            throw new RuntimeException('Payment is not a cheque.');
        }

        if ($payment->cheque_status !== 'submitted') {
            throw new RuntimeException("Cheque is already {$payment->cheque_status}.");
        }

        DB::transaction(function () use ($payment, $bouncedBy, $customBounceFee): void {
            // Reverse the payment
            $payment->update(['cheque_status' => 'bounced', 'is_reversed' => true]);

            $invoice   = $payment->invoice;
            $newPaid   = round((float) $invoice->amount_paid - (float) $payment->amount, 2);
            $newPaid   = max($newPaid, 0);
            $status    = $newPaid > 0 ? 'partial' : 'unpaid';

            // Determine bounce fee
            $config    = PaymentConfig::where('school_id', $payment->school_id)->first();
            $bounceFee = $customBounceFee ?? ($config ? (float) $config->bounce_fee_amount : 0.0);

            $invoice->update([
                'amount_paid' => $newPaid,
                'amount_due'  => round((float) $invoice->amount_due + $bounceFee, 2),
                'status'      => $status,
                'note'        => ($invoice->note ? $invoice->note . ' | ' : '') . "Cheque #{$payment->cheque_number} bounced.",
            ]);

            event(new ChequeBounced($payment->fresh()));
        });
    }
}
