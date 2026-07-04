<?php

namespace App\Modules\Website\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishPageLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Defaults to the latest revision if omitted.
            'layout_id' => ['nullable', 'integer'],
        ];
    }
}
