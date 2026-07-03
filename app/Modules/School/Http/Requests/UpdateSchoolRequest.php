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
            'name' => ['sometimes', 'string', 'max:255'],
            'institution_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'institution_code_label' => ['sometimes', 'string', 'max:50'],
            'school_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'technical_branch_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'established' => ['sometimes', 'nullable', 'date'],
            'address' => ['sometimes', 'nullable', 'string'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2', 'alpha', 'uppercase'],
            'email' => ['sometimes', 'nullable', 'email'],
            'currency' => ['sometimes', 'string', 'size:3', 'alpha', 'uppercase'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'academic_year_pattern' => ['sometimes', 'string', 'in:jan_dec,apr_mar,jul_jun,sep_aug'],
            'logo' => ['sometimes', 'nullable', 'string'],
            'sms_api_key' => ['sometimes', 'nullable', 'string'],
            'sms_sender_id' => ['sometimes', 'nullable', 'string', 'max:20'],
            'sms_cost_per_segment' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'auto_due_enabled' => ['sometimes', 'boolean'],
            'fine_per_day' => ['sometimes', 'numeric', 'min:0'],
            'quick_payment_process' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
