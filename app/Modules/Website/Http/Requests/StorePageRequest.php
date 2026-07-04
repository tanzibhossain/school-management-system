<?php

namespace App\Modules\Website\Http\Requests;

use App\Modules\Website\Models\Page;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            // Auto-generated from title if omitted (see PageService::resolveSlug) —
            // if given explicitly, just format-checked here; uniqueness/reserved-word
            // de-duplication is handled by the service so a trivial collision never
            // hard-errors the request.
            'slug' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_desc' => ['nullable', 'string', 'max:320'],
            'og_image' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(Page::STATUSES)],
        ];
    }
}
