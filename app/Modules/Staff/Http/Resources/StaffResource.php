<?php

namespace App\Modules\Staff\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Staff\Models\Staff */
class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'employee_id'     => $this->employee_id,
            'name'            => $this->name,
            'gender'          => $this->gender,
            'dob'             => $this->dob?->toDateString(),
            'blood_group'     => $this->blood_group,
            'religion'        => $this->religion,
            'nationality'     => $this->nationality,
            'mother_tongue'   => $this->mother_tongue,
            'photo'           => $this->photo,
            'joining_date'    => $this->joining_date?->toDateString(),
            'leaving_date'    => $this->leaving_date?->toDateString(),
            'employment_type' => $this->employment_type,
            'basic_salary'    => $this->basic_salary,
            'rfid_number'     => $this->rfid_number,
            'status'          => $this->status,
            'designation'     => $this->whenLoaded('designation', fn () => [
                'id'   => $this->designation?->id,
                'name' => $this->designation?->name,
            ]),
            'department'      => $this->whenLoaded('department', fn () => [
                'id'   => $this->department?->id,
                'name' => $this->department?->name,
            ]),
            'academics'       => StaffAcademicResource::collection($this->whenLoaded('academics')),
            'addresses'       => $this->whenLoaded('addresses'),
            'experiences'     => $this->whenLoaded('experiences'),
            'documents'       => $this->whenLoaded('documents'),
        ];
    }
}
