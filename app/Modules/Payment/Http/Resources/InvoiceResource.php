<?php

namespace App\Modules\Payment\Http\Resources;

use App\Modules\Payment\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'month' => $this->month,
            'amount_due' => $this->amount_due,
            'currency' => $this->currency,
            'amount_paid' => $this->amount_paid,
            'credit_applied' => $this->credit_applied,
            'remaining' => $this->remainingAmount(),
            'status' => $this->status,
            'due_date' => $this->due_date->toDateString(),
            'note' => $this->note,
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
