<?php

namespace App\Modules\Leave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectStaffLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // StaffLeaveService additionally enforces admin-only decisions
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
