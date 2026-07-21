<?php

namespace App\Modules\Academic\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassRoutineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_id' => $this->class_id,
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'room_id' => $this->room_id,
            'period_id' => $this->period_id,
            'shift_id' => $this->shift_id,
            'day_of_week' => $this->day_of_week,
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'room' => new RoutineRoomResource($this->whenLoaded('room')),
            'period' => new RoutinePeriodResource($this->whenLoaded('period')),
            'shift' => new AcademicShiftResource($this->whenLoaded('shift')),
        ];
    }
}
