<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\Student */
class StudentListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'admission_number' => $this->admission_number,
            'student_id'       => $this->student_id,
            'name'             => $this->name,
            'gender'           => $this->gender,
            'photo'            => $this->photo,
            'status'           => $this->status,
            'current_class'    => $this->whenLoaded('currentAcademic', fn () => [
                'class'   => $this->currentAcademic?->schoolClass?->name,
                'section' => $this->currentAcademic?->section?->name,
                'year'    => $this->currentAcademic?->year?->name,
            ]),
            'primary_guardian' => $this->whenLoaded('primaryGuardian', fn () => $this->primaryGuardian ? [
                'name'  => $this->primaryGuardian->name,
                'phone' => $this->primaryGuardian->phone,
            ] : null),
        ];
    }
}
