<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Payment\Models\PaymentConfig */
class PaymentConfigResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'invoice_prefix'      => $this->invoice_prefix,
            'invoice_last_seq'    => $this->invoice_last_seq,
            'receipt_prefix'      => $this->receipt_prefix,
            'receipt_last_seq'    => $this->receipt_last_seq,
            'bkash_fee_pct'       => $this->bkash_fee_pct,
            'sslcommerz_fee_pct'  => $this->sslcommerz_fee_pct,
            'bounce_fee_amount'   => $this->bounce_fee_amount,
            // Gateway URLs only — credentials never exposed
            'bkash_configured'    => ! empty($this->bkash_app_key),
            'bkash_base_url'      => $this->bkash_base_url,
            'sslcommerz_configured' => ! empty($this->sslcommerz_store_id),
            'sslcommerz_base_url' => $this->sslcommerz_base_url,
        ];
    }
}
