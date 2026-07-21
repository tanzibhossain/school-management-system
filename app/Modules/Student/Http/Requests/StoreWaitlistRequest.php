<?php

namespace App\Modules\Student\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWaitlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'class_id' => 'required|integer|exists:classes,id',
            'section_id' => 'nullable|integer|exists:sections,id',
            'applicant_name' => 'required|string|max:150',
            'guardian_name' => 'required|string|max:150',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'nullable|email',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
