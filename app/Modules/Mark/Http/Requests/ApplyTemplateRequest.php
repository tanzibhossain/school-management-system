<?php

namespace App\Modules\Mark\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'template' => ['required', 'string', 'max:50'],
        ];
    }
}
