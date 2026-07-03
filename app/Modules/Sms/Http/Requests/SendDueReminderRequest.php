<?php

namespace App\Modules\Sms\Http\Requests;

use App\Modules\Sms\Models\SmsBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendDueReminderRequest extends FormRequest
{
    /** Financial trigger — admin+accountant only, matches Report's gating. */
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('accountant:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scope' => ['sometimes', Rule::in(SmsBatch::SCOPES)],
            'class_id' => ['required_if:scope,class', 'nullable', 'integer', 'exists:classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'target_ids' => ['required_if:scope,single', 'nullable', 'array', 'min:1'],
            'target_ids.*' => ['integer'],
        ];
    }
}
