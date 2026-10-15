<?php

namespace App\Modules\Staff\Http\Requests;

use App\Modules\Staff\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ability checked via middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app('current_school_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'religion' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'mother_tongue' => ['nullable', 'string', 'max:50'],
            'joining_date' => ['nullable', 'date'],
            'employment_type' => ['sometimes', 'required', Rule::in(Staff::EMPLOYMENT_TYPES)],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'rfid_number' => ['nullable', 'string', 'max:30'],
            'designation_id' => ['nullable', 'integer', "exists:designations,id,school_id,{$schoolId}"],
            'department_id' => ['nullable', 'integer', "exists:departments,id,school_id,{$schoolId}"],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
