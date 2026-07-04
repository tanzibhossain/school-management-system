<?php

namespace App\Modules\Website\Http\Resources\Public;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately minimal — a public website block shows far less than the
 * internal StaffResource (no salary, RFID, employee ID, etc.). Built inside
 * Website rather than reusing Staff's own resources, so what's public is
 * curated here, not accidentally inherited from an internal-facing shape.
 *
 * @mixin Staff
 */
class PublicStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'designation' => $this->whenLoaded('designation', fn () => $this->designation?->name),
            'department' => $this->whenLoaded('department', fn () => $this->department?->name),
        ];
    }
}
