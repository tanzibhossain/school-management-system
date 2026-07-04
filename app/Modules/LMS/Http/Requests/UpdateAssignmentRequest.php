<?php

namespace App\Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'date'],
            'max_marks' => ['sometimes', 'integer', 'min:1'],
            'allow_late_submission' => ['sometimes', 'boolean'],
        ];
    }
}
