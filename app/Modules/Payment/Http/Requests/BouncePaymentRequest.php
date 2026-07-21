<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BouncePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'bounce_fee' => ['nullable', 'numeric', 'min:0'],  // overrides config default
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
