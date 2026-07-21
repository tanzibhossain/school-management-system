<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = app('current_school_id');

        return [
            // Student profile
            'admission_number' => "required|string|max:30|unique:students,admission_number,NULL,id,school_id,{$schoolId}",
            'name' => 'required|string|max:150',
            'dob' => 'nullable|date',
            'gender' => 'required|in:male,female,other',
            'blood_group' => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:50',
            'mother_tongue' => 'nullable|string|max:50',
            'photo' => 'nullable|image|max:2048',

            // Academic placement (required at enrolment)
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'required|integer|exists:sections,id',
            'version_id' => 'nullable|integer|exists:academic_versions,id',
            'group_id' => 'nullable|integer|exists:academic_groups,id',
            'shift_id' => 'nullable|integer|exists:academic_shifts,id',
            'roll_number' => 'nullable|string|max:20',

            // Sibling linking (optional)
            'sibling_student_id' => "nullable|string|exists:students,student_id,school_id,{$schoolId}",
            'sibling_admission_number' => "nullable|string|exists:students,admission_number,school_id,{$schoolId}",

            // Guardians (optional array)
            'guardians' => 'nullable|array|max:4',
            'guardians.*.relation' => 'required|in:father,mother,local_guardian,other',
            'guardians.*.name' => 'required|string|max:150',
            'guardians.*.phone' => 'nullable|string|max:20',
            'guardians.*.email' => 'nullable|email',
            'guardians.*.occupation' => 'nullable|string|max:100',
            'guardians.*.is_primary' => 'boolean',

            // Addresses
            'addresses' => 'nullable|array',
            'addresses.*.type' => 'required|in:present,permanent',
            'addresses.*.address' => 'nullable|string',
            'addresses.*.district' => 'nullable|string|max:100',
            'addresses.*.thana' => 'nullable|string|max:100',
            'addresses.*.post_code' => 'nullable|string|max:20',
        ];
    }
}
