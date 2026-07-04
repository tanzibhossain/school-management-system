<?php

namespace App\Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayslipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('accountant:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
