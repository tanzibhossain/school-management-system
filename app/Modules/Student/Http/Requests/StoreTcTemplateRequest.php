<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTcTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:150',
            'template_body'           => 'required|string',
            'footer_text'             => 'nullable|string',
            'signatory_name'          => 'nullable|string|max:150',
            'signatory_designation'   => 'nullable|string|max:150',
            'is_default'              => 'boolean',
        ];
    }
}
