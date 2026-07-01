<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => 'sometimes|string|max:150',
            'dob'           => 'nullable|date',
            'gender'        => 'sometimes|in:male,female,other',
            'blood_group'   => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'religion'      => 'nullable|string|max:50',
            'nationality'   => 'nullable|string|max:50',
            'mother_tongue' => 'nullable|string|max:50',
            'photo'         => 'nullable|image|max:2048',
        ];
    }
}
