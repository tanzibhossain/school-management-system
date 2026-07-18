<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Generic, registry-driven shape — one structure for every gateway:
     *   { payment_mode, invoice_prefix, receipt_prefix, bounce_fee_amount,
     *     gateways: { <slug>: { enabled, fee_pct, credentials: { <key>: <value> } } } }
     * The controller only applies slugs available to the school's country.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_mode'          => ['sometimes', 'in:offline,online,both'],
            'invoice_prefix'        => ['sometimes', 'nullable', 'string', 'max:20'],
            'receipt_prefix'        => ['sometimes', 'nullable', 'string', 'max:20'],
            'bounce_fee_amount'     => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'gateways'              => ['sometimes', 'array'],
            'gateways.*'            => ['array'],
            'gateways.*.enabled'    => ['sometimes', 'boolean'],
            'gateways.*.fee_pct'    => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'gateways.*.credentials' => ['sometimes', 'array'],
        ];
    }
}
