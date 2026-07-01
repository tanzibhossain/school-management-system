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
            'eiin_code'              => $this->eiin_code,
            'school_code'            => $this->school_code,
            'technical_branch_code'  => $this->technical_branch_code,
            'established'            => $this->established?->format('Y-m-d'),
            'address'                => $this->address,
            'email'                  => $this->email,
            'logo'                   => $this->logo,
            'sms_sender_id'          => $this->sms_sender_id,
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
