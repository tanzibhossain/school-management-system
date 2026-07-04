<?php

namespace App\Modules\LMS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['required', 'date'],
            'max_marks' => ['required', 'integer', 'min:1'],
            'allow_late_submission' => ['sometimes', 'boolean'],
        ];
    }
}
