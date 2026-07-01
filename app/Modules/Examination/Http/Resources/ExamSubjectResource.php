<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'exam_id'             => $this->exam_id,
            'subject_relation_id' => $this->subject_relation_id,
            // Eager-loaded: ->with('subjectRelation.subject')
            'subject_name'        => $this->whenLoaded(
                'subjectRelation',
                fn () => $this->subjectRelation->subject->name ?? null,
            ),
            'subject_code'        => $this->whenLoaded(
                'subjectRelation',
                fn () => $this->subjectRelation->subject->sub_code ?? null,
            ),
            'exam_date'           => $this->exam_date?->toDateString(),
            'start_time'          => $this->start_time,
            'end_time'            => $this->end_time,
            'full_marks'          => $this->full_marks,
            'pass_marks'          => $this->pass_marks,
        ];
    }
}
