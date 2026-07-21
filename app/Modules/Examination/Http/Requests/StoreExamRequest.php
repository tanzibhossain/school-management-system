<?php

namespace App\Modules\Examination\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'academic_year_id' => ['required', 'integer'],
            'class_id' => ['required', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'group_id' => ['nullable', 'integer'],
            'version_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:200'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'seating_strategy' => ['in:sequential,interleave_group,interleave_section,anti_adjacency'],
        ];
    }
}
