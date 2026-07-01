<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoteStudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'class_id'         => 'required|integer|exists:classes,id',
            'section_id'       => 'required|integer|exists:sections,id',
            'version_id'       => 'nullable|integer|exists:academic_versions,id',
            'group_id'         => 'nullable|integer|exists:academic_groups,id',
            'shift_id'         => 'nullable|integer|exists:academic_shifts,id',
            'roll_number'      => 'nullable|string|max:20',
        ];
    }
}
