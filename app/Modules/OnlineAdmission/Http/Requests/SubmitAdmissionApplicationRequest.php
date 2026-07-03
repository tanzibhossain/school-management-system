<?php

namespace App\Modules\OnlineAdmission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Public endpoint — no login, applicant submits their own application. */
class SubmitAdmissionApplicationRequest extends FormRequest
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
            'applicant_name' => ['required', 'string', 'max:150'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'desired_class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'desired_academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$schoolId}"],
            'guardian_name' => ['required', 'string', 'max:150'],
            'guardian_phone' => ['required', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email'],
            'guardian_relation' => ['required', Rule::in(['father', 'mother', 'local_guardian', 'other'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
