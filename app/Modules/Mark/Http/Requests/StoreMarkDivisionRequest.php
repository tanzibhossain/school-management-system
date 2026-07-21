<?php

namespace App\Modules\Mark\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMarkDivisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'exam_subject_id' => ['required', 'integer', 'exists:exam_subjects,id'],
            'name' => ['required', 'string', 'max:50'],
            'max_marks' => ['required', 'numeric', 'min:0.5'],
            'pass_mark' => ['nullable', 'numeric', 'min:0', 'lte:max_marks'],
            'display_order' => ['sometimes', 'integer', 'min:0', 'max:255'],
        ];
    }
}
