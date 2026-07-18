<?php

namespace App\Modules\Payment\Services;

use App\Modules\Payment\Models\PaymentConfig;
use Illuminate\Support\Facades\DB;

/**
 * Generates sequential, collision-free invoice and receipt numbers.
 * Uses DB::transaction + lockForUpdate — same pattern as StudentIdGeneratorService.
 */
class PaymentNumberGeneratorService
{
    /** Default config values for first-time generation. */
    private const DEFAULTS = [
        'invoice_prefix'    => 'INV',
        'invoice_last_seq'  => 0,
        'receipt_prefix'    => 'REC',
        'receipt_last_seq'  => 0,
        'bounce_fee_amount' => 0.00,
    ];

    public function nextInvoiceNumber(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId): string {
            $config = PaymentConfig::where('school_id', $schoolId)
                ->lockForUpdate()
                ->firstOrCreate(['school_id' => $schoolId], self::DEFAULTS);

            $seq = $config->invoice_last_seq + 1;
            $config->update(['invoice_last_seq' => $seq]);

            return sprintf('%s-%s-%05d', $config->invoice_prefix, now()->format('Y'), $seq);
        });
    }

    public function nextReceiptNumber(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId): string {
            $config = PaymentConfig::where('school_id', $schoolId)
                ->lockForUpdate()
                ->firstOrCreate(['school_id' => $schoolId], self::DEFAULTS);

            $seq = $config->receipt_last_seq + 1;
            $config->update(['receipt_last_seq' => $seq]);

            return sprintf('%s-%s-%05d', $config->receipt_prefix, now()->format('Y'), $seq);
        });
    }
}
