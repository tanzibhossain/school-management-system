<?php

namespace App\Modules\FeeItem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeeItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'integer', 'exists:fee_categories,id'],
            'class_id' => ['sometimes', 'nullable', 'integer', 'exists:classes,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'frequency' => ['sometimes', 'in:monthly,quarterly,yearly,one_time'],
            'due_day' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:28'],
            'is_mandatory' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
