<?php

namespace App\Modules\Attendance\Http\Resources;

use App\Modules\Attendance\Models\StaffAttendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StaffAttendance */
class StaffAttendanceResource extends JsonResource
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
            'date' => $this->date->toDateString(),
            'check_in' => $this->check_in?->toIso8601String(),
            'check_out' => $this->check_out?->toIso8601String(),
            'source' => $this->source,
            'is_auto_closed' => $this->is_auto_closed,
            'is_incomplete' => $this->is_incomplete,
            'note' => $this->note,
        ];
    }
}
