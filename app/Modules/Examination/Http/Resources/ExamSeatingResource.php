<?php

namespace App\Modules\Examination\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSeatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'student_id' => $this->student_id,
            'exam_roll' => $this->exam_roll,
            // Seat info (null if hall seat was deleted/regenerated)
            'seat_label' => $this->hallSeat?->label,
            'row' => $this->hallSeat?->row,
            'side' => $this->hallSeat?->side,
            'position' => $this->hallSeat?->position,
            // Denormalized for admit card rendering — no join needed
            'group_id' => $this->group_id,
            'section_id' => $this->section_id,
            // Student name when loaded
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'admission_number' => $this->student->admission_number,
            ]),
        ];
    }
}
