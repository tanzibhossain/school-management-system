<?php

namespace App\Modules\Attendance\Http\Resources;

use App\Modules\Attendance\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceSetting */
class AttendanceSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'auto_close_policy' => $this->auto_close_policy,
            'max_shift_hours' => $this->max_shift_hours,
            'edit_window_days' => $this->edit_window_days,
            'late_threshold_minutes' => $this->late_threshold_minutes,
            'leave_counts_in_denominator' => $this->leave_counts_in_denominator,
        ];
    }
}
