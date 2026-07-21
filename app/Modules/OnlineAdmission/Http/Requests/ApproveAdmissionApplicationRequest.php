<?php

namespace App\Modules\OnlineAdmission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveAdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app('current_school_id');

        return [
            // admission_number is never auto-generated anywhere in this codebase
            // (matches StoreStudentRequest / DataImport's row importer) — approval
            // still requires one explicit input even though it's otherwise automatic.
            'admission_number' => ['required', 'string', 'max:30', "unique:students,admission_number,NULL,id,school_id,{$schoolId}"],
            // No section was captured at application time — placement is decided now.
            'section_id' => ['required', 'integer', 'exists:sections,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'roll_number' => ['nullable', 'string', 'max:20'],
        ];
    }
}
