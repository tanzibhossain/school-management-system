<?php

namespace App\Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffPunchRequest extends FormRequest
{
    public function authorize(): bool
    {
        // RFID devices authenticate with a device token carrying admin:attendance ability
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rfid_number' => ['required', 'string', 'max:30'],
        ];
    }
}
