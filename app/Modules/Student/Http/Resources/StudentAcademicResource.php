<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\StudentAcademic */
class StudentAcademicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'year'             => $this->whenLoaded('year', fn () => $this->year?->name),
            'class_id'         => $this->class_id,
            'class'            => $this->whenLoaded('schoolClass', fn () => $this->schoolClass?->name),
            'section_id'       => $this->section_id,
            'section'          => $this->whenLoaded('section', fn () => $this->section?->name),
            'version'          => $this->whenLoaded('version', fn () => $this->version?->name),
            'group'            => $this->whenLoaded('group', fn () => $this->group?->name),
            'shift'            => $this->whenLoaded('shift', fn () => $this->shift?->name),
            'roll_number'      => $this->roll_number,
            'is_current'       => $this->is_current,
            'promoted_at'      => $this->promoted_at,
        ];
    }
}
