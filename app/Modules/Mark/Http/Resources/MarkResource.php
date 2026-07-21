<?php

namespace App\Modules\Mark\Http\Resources;

use App\Modules\Mark\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Mark */
class MarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'name' => $this->student->name,
                'admission_number' => $this->student->admission_number,
            ]),
            'mark_division_id' => $this->mark_division_id,
            'marks_obtained' => $this->is_absent ? 'Ab' : $this->marks_obtained,
            'is_absent' => $this->is_absent,
            'grace_marks' => $this->grace_marks,
            'grace_given_by' => $this->grace_given_by,
            'is_locked' => $this->isLocked(),
        ];
    }
}
