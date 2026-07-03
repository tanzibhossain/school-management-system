<?php

namespace App\Modules\IdCard\Http\Requests;

use App\Modules\IdCard\Models\IdCardBatch;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestIdCardBatchRequest extends FormRequest
{
    /** Staff cards are admin-only (HR-sensitive); student cards also allow teachers. */
    public function authorize(): bool
    {
        if ($this->user()->tokenCan('admin:*')) {
            return true;
        }

        return $this->input('type') === 'student' && $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(IdCardBatch::TYPES)],
            'template_id' => ['required', 'integer', 'exists:id_card_templates,id'],
            'scope' => ['required', Rule::in(IdCardBatch::SCOPES)],
            'class_id' => ['required_if:scope,class', 'nullable', 'integer', 'exists:classes,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'target_ids' => ['required_if:scope,single', 'nullable', 'array', 'min:1'],
            'target_ids.*' => ['integer'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator): void {
            // 'class' scope means "everyone in this class/section" — staff have no
            // class/section concept, so it's only meaningful for student cards.
            if ($this->input('scope') === 'class' && $this->input('type') === 'staff') {
                $validator->errors()->add('scope', 'The class scope is only available for student ID cards.');
            }
        });
    }
}
