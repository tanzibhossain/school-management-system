<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'exam_type'        => new ExamTypeResource($this->whenLoaded('examType')),
            'academic_year_id' => $this->academic_year_id,
            'class_id'         => $this->class_id,
            'section_id'       => $this->section_id,
            'group_id'         => $this->group_id,
            'version_id'       => $this->version_id,
            'start_date'       => $this->start_date?->toDateString(),
            'end_date'         => $this->end_date?->toDateString(),
            'status'           => $this->status,
            'is_ongoing'       => $this->is_ongoing,  // computed — not stored
            'seating_strategy' => $this->seating_strategy,
            'subjects_count'   => $this->when(
                ! $this->relationLoaded('subjects'),
                $this->subjects_count,
            ),
            'subjects'         => ExamSubjectResource::collection($this->whenLoaded('subjects')),
            'created_at'       => $this->created_at,
        ];
    }
}
