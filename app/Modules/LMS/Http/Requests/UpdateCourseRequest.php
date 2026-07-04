<?php

namespace App\Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'class_id' => ['sometimes', 'integer', 'exists:classes,id'],
            'subject_id' => ['sometimes', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:staff,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
