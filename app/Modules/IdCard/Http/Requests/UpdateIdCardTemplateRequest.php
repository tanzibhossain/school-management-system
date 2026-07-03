<?php

namespace App\Modules\IdCard\Http\Requests;

use App\Modules\IdCard\Models\IdCardTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIdCardTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /**
     * type is intentionally not editable — a template is created for a
     * given type and re-typing it would orphan its meaning; make a new one.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'layout' => ['sometimes', Rule::in(IdCardTemplate::LAYOUTS)],
            'background_color' => ['sometimes', 'string', 'max:20'],
            'accent_color' => ['sometimes', 'string', 'max:20'],
            'logo_path' => ['nullable', 'string'],
            'font' => ['sometimes', Rule::in(IdCardTemplate::FONTS)],
            'visible_fields' => ['sometimes', 'array'],
            'visible_fields.*' => ['string'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
