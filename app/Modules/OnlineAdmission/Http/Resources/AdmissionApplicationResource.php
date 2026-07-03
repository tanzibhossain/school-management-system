<?php

namespace App\Modules\OnlineAdmission\Http\Resources;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AdmissionApplication */
class AdmissionApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'applicant_name' => $this->applicant_name,
            'gender' => $this->gender,
            'dob' => $this->dob?->toDateString(),
            'blood_group' => $this->blood_group,
            'desired_class_id' => $this->desired_class_id,
            'desired_class' => $this->whenLoaded('desiredClass', fn () => $this->desiredClass->name),
            'desired_academic_year_id' => $this->desired_academic_year_id,
            'desired_academic_year' => $this->whenLoaded('desiredAcademicYear', fn () => $this->desiredAcademicYear->year),
            'guardian_name' => $this->guardian_name,
            'guardian_phone' => $this->guardian_phone,
            'guardian_email' => $this->guardian_email,
            'guardian_relation' => $this->guardian_relation,
            'notes' => $this->notes,
            'decision_reason' => $this->decision_reason,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_student_id' => $this->created_student_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
