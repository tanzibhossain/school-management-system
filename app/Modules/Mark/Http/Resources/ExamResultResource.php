<?php

namespace App\Modules\Mark\Http\Resources;

use App\Modules\Mark\Models\ExamResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ExamResult
 *
 * merit_position is hidden from non-admin viewers when the class's
 * show_merit_position setting is off (per-school privacy toggle).
 * Controllers set the 'show_merit' request attribute before rendering.
 */
class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $showMerit = $request->attributes->get(
            'show_merit',
            $request->user()?->tokenCan('admin:*') ?? false,
        );

        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'admission_number' => $this->student->admission_number,
            ]),
            'total_marks' => $this->total_marks,
            'total_possible' => $this->total_possible,
            'percentage' => $this->percentage,
            'grade' => $this->grade,
            'gpa' => $this->gpa,
            'is_pass' => $this->is_pass,
            'merit_position' => $showMerit ? $this->merit_position : null,
            'subject_breakdown' => $this->subject_breakdown,
            'is_locked' => $this->is_locked,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
