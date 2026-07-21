<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'due_date' => ['required', 'date'],
            'discount_id' => ['nullable', 'integer', 'exists:fee_discounts,id'],
            // Single student OR bulk class — one is required
            'student_id' => ['required_without:class_id', 'nullable', 'integer'],
            'class_id' => ['required_without:student_id', 'nullable', 'integer', 'exists:classes,id'],
        ];
    }
}
