<?php

namespace App\Modules\Leave\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Leave\Models\StaffLeaveRequest */
class StaffLeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'staff_id' => $this->staff_id,
            'staff'    => $this->whenLoaded('staff', fn () => [
                'id'          => $this->staff->id,
                'name'        => $this->staff->name,
                'employee_id' => $this->staff->employee_id,
            ]),
            'leave_type_id'    => $this->leave_type_id,
            'leave_type'       => $this->whenLoaded('leaveType', fn () => $this->leaveType->name),
            'from_date'        => $this->from_date->toDateString(),
            'to_date'          => $this->to_date->toDateString(),
            'working_days'     => $this->working_days,
            'reason'           => $this->reason,
            'attachment_path'  => $this->attachment_path,
            'status'           => $this->status,
            'requested_by'     => $this->requested_by,
            'approved_by'      => $this->approved_by,
            'approved_at'      => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
