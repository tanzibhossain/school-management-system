<?php

namespace App\Modules\School\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolPhoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'label' => $this->label,
            'is_primary' => $this->is_primary,
        ];
    }
}
