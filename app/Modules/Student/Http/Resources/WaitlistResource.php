<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\StudentWaitlist */
class WaitlistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'class'          => $this->whenLoaded('schoolClass', fn () => $this->schoolClass?->name),
            'section'        => $this->whenLoaded('section', fn () => $this->section?->name),
            'applicant_name' => $this->applicant_name,
            'guardian_name'  => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'guardian_email' => $this->guardian_email,
            'position'       => $this->position,
            'status'         => $this->status,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at,
        ];
    }
}
