<?php

namespace App\Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssignmentRequest extends FormRequest
{
    /** Students submit their own work — not admin/teacher on their behalf. */
    public function authorize(): bool
    {
        return $this->user()->tokenCan('student:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'],
        ];
    }
}
