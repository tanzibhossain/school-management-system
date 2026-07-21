<?php

namespace App\Modules\Mark\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'weights' => ['required', 'array', 'min:1'],
            'weights.*.exam_id' => ['required', 'integer', 'exists:exams,id'],
            'weights.*.weight_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $weights = collect($this->input('weights', []));

                if (round($weights->sum('weight_percent'), 2) !== 100.00) {
                    $validator->errors()->add('weights', 'Exam weights must sum to exactly 100.');
                }

                if ($weights->pluck('exam_id')->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('weights', 'Each exam may appear only once.');
                }
            },
        ];
    }
}
