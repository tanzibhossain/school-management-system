<?php

namespace App\Modules\School\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'institution_code'       => $this->institution_code,
            'institution_code_label' => $this->institution_code_label,
            'school_code'            => $this->school_code,
            'technical_branch_code'  => $this->technical_branch_code,
            'established'            => $this->established?->format('Y-m-d'),
            'address'                => $this->address,
            'country_code'           => $this->country_code,
            'email'                  => $this->email,
            'currency'               => $this->currency,
            'timezone'               => $this->timezone,
            'locale'                 => $this->locale,
            'academic_year_pattern'  => $this->academic_year_pattern,
            'logo'                   => $this->logo,
            'sms_sender_id'          => $this->sms_sender_id,
            'sms_cost_per_segment'   => $this->sms_cost_per_segment,
            // sms_api_key is in $hidden — never exposed
            'auto_due_enabled'       => $this->auto_due_enabled,
            'fine_per_day'           => $this->fine_per_day,
            'quick_payment_process'  => $this->quick_payment_process,
            'is_active'              => $this->is_active,
            'phones'                 => SchoolPhoneResource::collection($this->whenLoaded('phones')),
            'opening_hours'          => SchoolOpeningHourResource::collection($this->whenLoaded('openingHours')),
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
