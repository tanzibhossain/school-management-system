<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferStudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason'      => 'required|in:transfer,withdrawal,completion',
            'template_id' => 'nullable|integer|exists:transfer_certificate_templates,id',
        ];
    }
}
