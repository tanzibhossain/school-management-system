<?php

namespace App\Modules\LMS\Http\Resources;

use App\Modules\LMS\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lesson */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'content_type' => $this->content_type,
            'body_text' => $this->content_type === 'text' ? $this->body_text : null,
            'video_url' => $this->content_type === 'video' ? $this->video_url : null,
            'file_path' => $this->content_type === 'file' ? $this->file_path : null,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
