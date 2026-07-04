<?php

namespace App\Modules\Payroll\Http\Requests;

use App\Modules\Payroll\Models\SalaryComponent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryComponentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('accountant:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'component_type' => ['required', Rule::in(SalaryComponent::TYPES)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
