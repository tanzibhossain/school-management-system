<?php

namespace App\Modules\Website\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** "Save as Template" from an open page's editor. */
class StorePageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolId = app('current_school_id');

        return [
            'page_id' => ['required', 'integer', "exists:pages,id,school_id,{$schoolId}"],
            'name' => ['required', 'string', 'max:150'],
        ];
    }
}
