<?php

namespace App\Modules\Payment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank_transfer,cheque,waiver'],
            // Bank transfer
            'transaction_ref' => ['required_if:method,bank_transfer', 'nullable', 'string', 'max:100'],
            // Cheque-specific
            'cheque_number' => ['required_if:method,cheque', 'nullable', 'string', 'max:30'],
            'bank_name' => ['required_if:method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_date' => ['required_if:method,cheque', 'nullable', 'date'],
            // Optional
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
