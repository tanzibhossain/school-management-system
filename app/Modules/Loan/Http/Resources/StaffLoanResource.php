<?php

namespace App\Modules\Loan\Http\Resources;

use App\Modules\Loan\Models\StaffLoan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StaffLoan */
class StaffLoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_id' => $this->staff_id,
            'staff' => $this->whenLoaded('staff', fn () => [
                'id' => $this->staff->id,
                'name' => $this->staff->name,
                'employee_id' => $this->staff->employee_id,
            ]),
            'requested_amount' => $this->requested_amount,
            'installment_count' => $this->installment_count,
            'reason' => $this->reason,
            'start_date' => $this->start_date->toDateString(),
            'status' => $this->status,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'schedules' => LoanScheduleResource::collection($this->whenLoaded('schedules')),
        ];
    }
}
