<?php

namespace App\Modules\Student\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Student\Models\Student */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'admission_number'    => $this->admission_number,
            'student_id'          => $this->student_id,
            'name'                => $this->name,
            'dob'                 => $this->dob?->toDateString(),
            'gender'              => $this->gender,
            'blood_group'         => $this->blood_group,
            'religion'            => $this->religion,
            'nationality'         => $this->nationality,
            'mother_tongue'       => $this->mother_tongue,
            'photo'               => $this->photo,
            'status'              => $this->status,
            're_admission_count'  => $this->re_admission_count,
            'current_academic'    => new StudentAcademicResource($this->whenLoaded('currentAcademic')),
            'academics'           => StudentAcademicResource::collection($this->whenLoaded('academics')),
            'guardians'           => StudentGuardianResource::collection($this->whenLoaded('guardians')),
            'addresses'           => StudentAddressResource::collection($this->whenLoaded('addresses')),
            'documents'           => StudentDocumentResource::collection($this->whenLoaded('documents')),
            'siblings'            => $this->whenLoaded('siblingLinks', fn () =>
                $this->siblingLinks->map(fn ($link) => [
                    'id'         => $link->sibling?->id,
                    'name'       => $link->sibling?->name,
                    'student_id' => $link->sibling?->student_id,
                ])
            ),
            'created_at'          => $this->created_at,
        ];
    }
}
