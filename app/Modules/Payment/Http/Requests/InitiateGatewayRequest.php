<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'callback_url' => ['required_without:success_url', 'nullable', 'url'],  // bKash
            'success_url' => ['required_without:callback_url', 'nullable', 'url'],  // SSLCommerz
            'fail_url' => ['required_with:success_url', 'nullable', 'url'],
            'cancel_url' => ['required_with:success_url', 'nullable', 'url'],
            'ipn_url' => ['required_with:success_url', 'nullable', 'url'],
        ];
    }
}
