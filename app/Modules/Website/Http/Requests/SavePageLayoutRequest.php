<?php

namespace App\Modules\Website\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** layout_json is opaque to Laravel — the Next.js/Craft.js editor owns its internal shape. */
class SavePageLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'layout_json' => ['required', 'array'],
        ];
    }
}
