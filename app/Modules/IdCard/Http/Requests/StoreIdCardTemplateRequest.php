<?php

namespace App\Modules\IdCard\Http\Requests;

use App\Modules\IdCard\Models\IdCardTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIdCardTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(IdCardTemplate::TYPES)],
            'name' => ['required', 'string', 'max:150'],
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
