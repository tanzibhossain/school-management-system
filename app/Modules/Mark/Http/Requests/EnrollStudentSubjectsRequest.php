<?php

namespace App\Modules\Mark\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.subject_relation_id' => ['required', 'integer', 'exists:subject_relations,id'],
            'subjects.*.is_optional' => ['sometimes', 'boolean'],
        ];
    }
}
