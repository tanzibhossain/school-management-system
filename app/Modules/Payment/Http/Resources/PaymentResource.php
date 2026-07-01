<?php

namespace App\Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Payment\Models\Payment */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'receipt_number'  => $this->receipt_number,
            'invoice_id'      => $this->invoice_id,
            'student_id'      => $this->student_id,
            'amount'          => $this->amount,
            'method'             => $this->method,
            'transaction_ref'    => $this->transaction_ref,
            'gateway_payment_id' => $this->gateway_payment_id,
            'gateway_status'     => $this->gateway_status,
            'cheque_number'   => $this->cheque_number,
            'bank_name'       => $this->bank_name,
            'cheque_date'     => $this->cheque_date?->toDateString(),
            'cheque_status'   => $this->cheque_status,
            'is_reversed'     => $this->is_reversed,
            'note'            => $this->note,
            'paid_at'         => $this->paid_at->toIso8601String(),
        ];
    }
}
