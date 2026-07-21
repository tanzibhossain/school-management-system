<?php

namespace App\Modules\Staff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'employment_type' => ['nullable', 'in:permanent,contractual,part_time'],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'designation_id' => ['nullable', 'integer', "exists:designations,id,school_id,{$schoolId}"],
            'department_id' => ['nullable', 'integer', "exists:departments,id,school_id,{$schoolId}"],
        ];
    }
}
