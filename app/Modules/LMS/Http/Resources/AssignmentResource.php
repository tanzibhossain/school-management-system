<?php

namespace App\Modules\LMS\Http\Resources;

use App\Modules\LMS\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'title' => $this->title,
            'instructions' => $this->instructions,
            'due_date' => $this->due_date?->toIso8601String(),
            'max_marks' => $this->max_marks,
            'allow_late_submission' => $this->allow_late_submission,
            'is_past_due' => $this->isPastDue(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
