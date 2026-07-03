<?php

namespace App\Modules\Certificate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'template_body' => ['sometimes', 'string'],
            'footer_text' => ['nullable', 'string'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_designation' => ['nullable', 'string', 'max:150'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
