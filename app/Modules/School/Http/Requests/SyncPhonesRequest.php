<?php

namespace App\Modules\School\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPhonesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:settings');
    }

    public function rules(): array
    {
        return [
            'phones'             => ['required', 'array'],
            'phones.*.phone'     => ['required', 'string', 'max:20'],
            'phones.*.label'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'phones.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
