<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentIdConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prefix' => 'required|string|max:20',
            'include_year' => 'boolean',
            'year_format' => 'in:YYYY,YY',
            'separator' => 'required|string|max:5',
            'sequence_length' => 'integer|min:2|max:8',
            'reset_yearly' => 'boolean',
        ];
    }
}
