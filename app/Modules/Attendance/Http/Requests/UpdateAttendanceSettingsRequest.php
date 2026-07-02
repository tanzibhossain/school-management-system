<?php

namespace App\Modules\Attendance\Http\Requests;

use App\Modules\Attendance\Models\AttendanceSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'auto_close_policy'           => ['sometimes', Rule::in(AttendanceSetting::AUTO_CLOSE_POLICIES)],
            'max_shift_hours'             => ['sometimes', 'integer', 'min:1', 'max:24'],
            'edit_window_days'            => ['sometimes', 'integer', 'min:0', 'max:90'],
            'late_threshold_minutes'      => ['sometimes', 'integer', 'min:0', 'max:240'],
            'leave_counts_in_denominator' => ['sometimes', 'boolean'],
        ];
    }
}
