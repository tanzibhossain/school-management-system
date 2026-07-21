<?php

namespace App\Modules\Mark\Http\Requests;

use App\Modules\Mark\Models\MarkSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMarkSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'mode' => ['sometimes', Rule::in(MarkSetting::MODES)],
            'result_strategy' => ['sometimes', Rule::in(MarkSetting::STRATEGIES)],
            'show_merit_position' => ['sometimes', 'boolean'],
            'grace_marks_cap' => ['sometimes', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
