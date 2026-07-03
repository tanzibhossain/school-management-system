<?php

namespace App\Modules\Certificate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'conduct_remark'  => ['required', 'string', 'max:2000'],
            'exam_id'         => ['nullable', 'integer', 'exists:exams,id'],
            'template_id'     => ['nullable', 'integer', 'exists:testimonial_templates,id'],
            'attendance_from' => ['nullable', 'date_format:Y-m-d'],
            'attendance_to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:attendance_from'],
        ];
    }
}
