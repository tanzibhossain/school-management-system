<?php

namespace App\Modules\Payroll\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('accountant:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
