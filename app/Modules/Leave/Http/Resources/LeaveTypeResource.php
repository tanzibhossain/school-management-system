<?php

namespace App\Modules\Leave\Http\Resources;

use App\Modules\Leave\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeaveType */
class LeaveTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'applies_to' => $this->applies_to,
            'max_days_per_year' => $this->max_days_per_year,
            'requires_attachment' => $this->requires_attachment,
            'is_paid' => $this->is_paid,
            'is_active' => $this->is_active,
        ];
    }
}
