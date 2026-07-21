<?php

namespace App\Modules\Payment\Http\Resources;

use App\Modules\Payment\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Refund */
class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_id' => $this->payment_id,
            'amount' => $this->amount,
            'processing_fee' => $this->processing_fee,
            'net_refund' => $this->net_refund,
            'method' => $this->method,
            'status' => $this->status,
            'gateway_ref' => $this->gateway_ref,
            'note' => $this->note,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
