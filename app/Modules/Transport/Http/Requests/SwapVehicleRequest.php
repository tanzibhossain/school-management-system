<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwapVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'replacement_vehicle_id' => ['required', 'integer', 'exists:transport_vehicles,id'],
        ];
    }
}
