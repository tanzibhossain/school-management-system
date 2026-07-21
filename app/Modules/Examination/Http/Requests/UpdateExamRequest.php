<?php

namespace App\Modules\Examination\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id' => ['sometimes', 'integer', 'exists:exam_types,id'],
            'title' => ['sometimes', 'string', 'max:200'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'seating_strategy' => ['in:sequential,interleave_group,interleave_section,anti_adjacency'],
            'section_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
            'version_id' => ['nullable', 'integer'],
        ];
    }
}
