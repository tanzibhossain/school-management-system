<?php

namespace App\Modules\Staff\Http\Requests;

use App\Modules\Staff\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReHireStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app('current_school_id');

        return [
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['sometimes', 'required', Rule::in(Staff::EMPLOYMENT_TYPES)],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'designation_id' => ['nullable', 'integer', "exists:designations,id,school_id,{$schoolId}"],
            'department_id' => ['nullable', 'integer', "exists:departments,id,school_id,{$schoolId}"],
        ];
    }
}
