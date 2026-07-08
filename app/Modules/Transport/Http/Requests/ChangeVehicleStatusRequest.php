<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeVehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // in_service is never set by hand — it happens via a route attach/swap.
            'status' => ['required', Rule::in(['available', 'out_of_service'])],
        ];
    }
}
