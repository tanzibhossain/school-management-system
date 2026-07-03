<?php

namespace App\Modules\Sms\Http\Requests;

use App\Modules\Sms\Models\SmsBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendManualSmsRequest extends FormRequest
{
    /** A teacher texting their own class's guardians is normal; not accountant/staff. */
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1600'],
            'scope' => ['required', Rule::in(SmsBatch::SCOPES)],
            'class_id' => ['required_if:scope,class', 'nullable', 'integer', 'exists:classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'target_ids' => ['required_if:scope,single', 'nullable', 'array', 'min:1'],
            'target_ids.*' => ['integer'],
        ];
    }
}
