<?php

namespace App\Modules\Loan\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitStaffLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fine-grained decision authority (admin/accountant only) lives in StaffLoanService
        return $this->user()->tokenCan('admin:*')
            || $this->user()->tokenCan('accountant:*')
            || $this->user()->tokenCan('staff:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'requested_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:60'],
            'reason' => ['required', 'string', 'max:1000'],
            'start_date' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
