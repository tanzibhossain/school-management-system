<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransportRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'fare' => ['sometimes', 'required', 'numeric', 'min:0'],
            'driver_id' => ['nullable', 'integer', 'exists:transport_drivers,id'],
            'academic_transport_id' => ['nullable', 'integer', 'exists:transports,id'],
            'is_active' => ['boolean'],
        ];
    }
}
