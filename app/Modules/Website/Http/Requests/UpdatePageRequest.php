<?php

namespace App\Modules\Website\Http\Requests;

use App\Modules\Website\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:150'],
            // A changed slug auto-creates a page_redirects row (see PageService::update).
            'slug' => ['sometimes', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'meta_desc' => ['sometimes', 'nullable', 'string', 'max:320'],
            'og_image' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::in(Page::STATUSES)],
        ];
    }
}
