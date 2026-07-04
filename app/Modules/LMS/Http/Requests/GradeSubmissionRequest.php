<?php

namespace App\Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'marks_awarded' => ['required', 'integer', 'min:0'],
            'teacher_feedback' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
