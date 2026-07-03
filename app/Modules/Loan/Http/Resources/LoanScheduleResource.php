<?php

namespace App\Modules\Loan\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Loan\Models\LoanSchedule */
class LoanScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'installment_number'  => $this->installment_number,
            'due_date'            => $this->due_date->toDateString(),
            'amount'              => $this->amount,
            'is_paid'             => $this->is_paid,
            'paid_amount'         => $this->paid_amount,
            'paid_at'             => $this->paid_at?->toIso8601String(),
        ];
    }
}
