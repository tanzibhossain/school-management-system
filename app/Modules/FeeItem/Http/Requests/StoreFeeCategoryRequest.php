<?php

namespace App\Modules\FeeItem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
