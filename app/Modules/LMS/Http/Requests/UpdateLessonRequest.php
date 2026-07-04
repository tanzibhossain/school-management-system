<?php

namespace App\Modules\LMS\Http\Requests;

use App\Modules\LMS\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tokenCan('admin:*') || $this->user()->tokenCan('teacher:*');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content_type' => ['sometimes', Rule::in(Lesson::CONTENT_TYPES)],
            'body_text' => ['sometimes', 'nullable', 'string'],
            'video_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'file' => ['sometimes', 'nullable', 'file', 'max:20480'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
