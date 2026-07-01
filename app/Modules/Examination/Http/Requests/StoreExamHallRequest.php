<?php

namespace App\Modules\Examination\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamHallRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                            => ['required', 'string', 'max:100'],
            'description'                     => ['nullable', 'string', 'max:255'],
            'layout_config'                   => ['required', 'array'],
            'layout_config.rows'              => ['required', 'integer', 'min:1', 'max:500'],
            'layout_config.sides'             => ['required', 'array', 'min:1', 'max:4'],
            'layout_config.sides.*.label'     => ['required', 'string', 'in:L,R'],
            'layout_config.sides.*.seats_per_row' => ['required', 'integer', 'min:1', 'max:20'],
            'layout_config.sides.*.blocked_rows'  => ['array'],
            'layout_config.sides.*.blocked_rows.*'=> ['integer', 'min:1'],
        ];
    }
}
