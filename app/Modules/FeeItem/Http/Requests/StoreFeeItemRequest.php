<?php

namespace App\Modules\FeeItem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id'      => ['required', 'integer', 'exists:fee_categories,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'class_id'         => ['nullable', 'integer', 'exists:classes,id'],
            'name'             => ['required', 'string', 'max:150'],
            'amount'           => ['required', 'numeric', 'min:0'],
            'frequency'        => ['required', 'in:monthly,quarterly,yearly,one_time'],
            'due_day'          => ['nullable', 'integer', 'min:1', 'max:28'],
            'is_mandatory'     => ['boolean'],
            'is_active'        => ['boolean'],
        ];
    }
}
