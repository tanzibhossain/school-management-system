<?php

namespace App\Modules\Leave\Http\Requests;

use App\Modules\Leave\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'applies_to' => ['sometimes', Rule::in(LeaveType::APPLIES_TO)],
            'max_days_per_year' => ['nullable', 'integer', 'min:0'],
            'requires_attachment' => ['sometimes', 'boolean'],
            'is_paid' => ['nullable', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
