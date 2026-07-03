<?php

namespace App\Modules\OnlineAdmission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectAdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:500'],
        ];
    }
}
