<?php

namespace App\Modules\Examination\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamHallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'layout_config' => ['sometimes', 'array'],
            'layout_config.rows' => ['required_with:layout_config', 'integer', 'min:1', 'max:500'],
            'layout_config.sides' => ['required_with:layout_config', 'array', 'min:1', 'max:4'],
            'layout_config.sides.*.label' => ['required', 'string', 'in:L,R'],
            'layout_config.sides.*.seats_per_row' => ['required', 'integer', 'min:1', 'max:20'],
            'layout_config.sides.*.blocked_rows' => ['array'],
            'layout_config.sides.*.blocked_rows.*' => ['integer', 'min:1'],
        ];
    }
}
