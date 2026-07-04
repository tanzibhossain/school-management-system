<?php

namespace App\Modules\LMS\Http\Requests;

use App\Modules\LMS\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content_type' => ['required', Rule::in(Lesson::CONTENT_TYPES)],
            'body_text' => ['required_if:content_type,text', 'nullable', 'string'],
            'video_url' => ['required_if:content_type,video', 'nullable', 'url', 'max:2048'],
            'file' => ['required_if:content_type,file', 'nullable', 'file', 'max:20480'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
