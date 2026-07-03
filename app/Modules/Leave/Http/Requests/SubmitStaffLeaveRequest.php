<?php

namespace App\Modules\Leave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitStaffLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated staff member (or admin on their behalf) may submit;
        // approval authority is checked separately in StaffLeaveService.
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('staff:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'from_date'     => ['required', 'date_format:Y-m-d'],
            'to_date'       => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'reason'        => ['required', 'string', 'max:1000'],
            'attachment'    => ['nullable', 'file', 'max:5120'],
        ];
    }
}
