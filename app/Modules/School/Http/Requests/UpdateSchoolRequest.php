<?php

namespace App\Modules\School\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:settings');
    }

    public function rules(): array
    {
        return [
            'name'                   => ['sometimes', 'string', 'max:255'],
            'eiin_code'              => ['sometimes', 'nullable', 'string', 'max:50'],
            'school_code'            => ['sometimes', 'nullable', 'string', 'max:50'],
            'technical_branch_code'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'established'            => ['sometimes', 'nullable', 'date'],
            'address'                => ['sometimes', 'nullable', 'string'],
            'email'                  => ['sometimes', 'nullable', 'email'],
            'logo'                   => ['sometimes', 'nullable', 'string'],
            'sms_api_key'            => ['sometimes', 'nullable', 'string'],
            'sms_sender_id'          => ['sometimes', 'nullable', 'string', 'max:20'],
            'auto_due_enabled'       => ['sometimes', 'boolean'],
            'fine_per_day'           => ['sometimes', 'numeric', 'min:0'],
            'quick_payment_process'  => ['sometimes', 'nullable', 'string'],
            'is_active'              => ['sometimes', 'boolean'],
        ];
    }
}
