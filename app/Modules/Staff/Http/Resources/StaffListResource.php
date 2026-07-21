<?php

namespace App\Modules\Staff\Http\Resources;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Staff */
class StaffListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'gender' => $this->gender,
            'photo' => $this->photo,
            'employment_type' => $this->employment_type,
            'status' => $this->status,
            'rfid_number' => $this->rfid_number,
            'designation' => $this->whenLoaded('designation', fn () => $this->designation
                ? ['id' => $this->designation->id, 'name' => $this->designation->name]
                : null),
            'department' => $this->whenLoaded('department', fn () => $this->department
                ? ['id' => $this->department->id, 'name' => $this->department->name]
                : null),
        ];
    }
}
