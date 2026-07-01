<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_prefix'        => ['sometimes', 'string', 'max:10'],
            'receipt_prefix'        => ['sometimes', 'string', 'max:10'],
            'bkash_fee_pct'         => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'sslcommerz_fee_pct'    => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'bounce_fee_amount'     => ['sometimes', 'numeric', 'min:0'],
            'bkash_app_key'         => ['sometimes', 'nullable', 'string'],
            'bkash_app_secret'      => ['sometimes', 'nullable', 'string'],
            'bkash_username'        => ['sometimes', 'nullable', 'string'],
            'bkash_password'        => ['sometimes', 'nullable', 'string'],
            'bkash_base_url'        => ['sometimes', 'nullable', 'url'],
            'sslcommerz_store_id'   => ['sometimes', 'nullable', 'string'],
            'sslcommerz_store_pass' => ['sometimes', 'nullable', 'string'],
            'sslcommerz_base_url'   => ['sometimes', 'nullable', 'url'],
        ];
    }
}
