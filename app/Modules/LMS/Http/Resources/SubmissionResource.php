<?php

namespace App\Modules\LMS\Http\Resources;

use App\Modules\LMS\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Submission */
class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'student_id' => $this->student_id,
            'file_path' => $this->file_path,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'late_submission' => $this->late_submission,
            'marks_awarded' => $this->marks_awarded,
            'teacher_feedback' => $this->teacher_feedback,
            'graded_at' => $this->graded_at?->toIso8601String(),
            'ai_check' => new SubmissionAiCheckResource($this->whenLoaded('aiCheck')),
        ];
    }
}
