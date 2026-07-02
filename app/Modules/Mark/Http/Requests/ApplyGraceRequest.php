<?php

namespace App\Modules\Mark\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyGraceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'grace_marks' => ['required', 'numeric', 'min:0'],
        ];
    }
}
