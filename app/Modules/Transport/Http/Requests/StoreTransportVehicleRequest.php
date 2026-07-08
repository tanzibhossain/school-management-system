<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'registration_no' => [
                'required', 'string', 'max:50',
                Rule::unique('transport_vehicles', 'registration_no')
                    ->where('school_id', app('current_school_id')),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            // New vehicles start in the pool; in_service is reached only by
            // attaching to a route, never set directly here.
            'status' => ['nullable', Rule::in(['available', 'out_of_service'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
