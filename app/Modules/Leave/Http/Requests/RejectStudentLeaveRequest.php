<?php

namespace App\Modules\Leave\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectStudentLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fine-grained check (class teacher of this section, or admin) lives in StudentLeaveService
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
