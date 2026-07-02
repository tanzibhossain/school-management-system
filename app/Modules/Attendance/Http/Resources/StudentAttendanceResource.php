<?php

namespace App\Modules\Attendance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Attendance\Models\StudentAttendance */
class StudentAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'student_id' => $this->student_id,
            'student'    => $this->whenLoaded('student', fn () => [
                'id'               => $this->student->id,
                'name'             => $this->student->name,
                'admission_number' => $this->student->admission_number,
            ]),
            'class_id'   => $this->class_id,
            'section_id' => $this->section_id,
            'date'       => $this->date->toDateString(),
            'status'     => $this->status,
            'note'       => $this->note,
            'recorded_by' => $this->recorded_by,
            'edited_by'  => $this->edited_by,
        ];
    }
}
