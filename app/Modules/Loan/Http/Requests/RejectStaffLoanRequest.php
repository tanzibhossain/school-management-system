<?php

namespace App\Modules\Loan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectStaffLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('accountant:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }
}
