<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTcTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:150',
            'template_body' => 'sometimes|string',
            'footer_text' => 'nullable|string',
            'signatory_name' => 'nullable|string|max:150',
            'signatory_designation' => 'nullable|string|max:150',
            'is_default' => 'boolean',
        ];
    }
}
