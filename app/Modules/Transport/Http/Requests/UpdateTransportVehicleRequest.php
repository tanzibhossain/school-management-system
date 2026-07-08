<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransportVehicleRequest extends FormRequest
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
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('transport_vehicles', 'registration_no')
                    ->where('school_id', app('current_school_id'))
                    ->ignore($this->route('id')),
            ],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
