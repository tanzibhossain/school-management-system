<?php

namespace App\Modules\Transport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'transport_route_id' => ['required', 'integer', 'exists:transport_routes,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'pickup_point' => ['nullable', 'string', 'max:150'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }
}
