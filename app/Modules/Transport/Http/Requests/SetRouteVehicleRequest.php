<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetRouteVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // null detaches the current vehicle (back to the pool).
            'vehicle_id' => ['present', 'nullable', 'integer', 'exists:transport_vehicles,id'],
        ];
    }
}
