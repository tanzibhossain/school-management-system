<?php

namespace App\Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            // Only an admin may assign a course to a DIFFERENT teacher —
            // enforced in the controller, not here (a teacher's own token
            // still needs to be able to omit this field entirely).
            'teacher_id' => ['sometimes', 'nullable', 'integer', 'exists:staff,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
