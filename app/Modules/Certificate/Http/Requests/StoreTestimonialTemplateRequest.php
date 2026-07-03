<?php

namespace App\Modules\Certificate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTestimonialTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'template_body' => ['required', 'string'],
            'footer_text' => ['nullable', 'string'],
            'signatory_name' => ['nullable', 'string', 'max:150'],
            'signatory_designation' => ['nullable', 'string', 'max:150'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
