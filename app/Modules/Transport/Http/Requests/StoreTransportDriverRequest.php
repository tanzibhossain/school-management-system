<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license_no' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'on_leave', 'inactive'])],
        ];
    }
}
