<?php

namespace App\Modules\Transport\Http\Resources;

use App\Modules\Transport\Models\TransportRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TransportRoute */
class TransportRouteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'fare' => $this->fare,
            'fee_item_id' => $this->fee_item_id,
            'academic_transport_id' => $this->academic_transport_id,
            'current_vehicle_id' => $this->current_vehicle_id,
            'driver_id' => $this->driver_id,
            'is_active' => $this->is_active,
            'vehicle' => new TransportVehicleResource($this->whenLoaded('vehicle')),
            'driver' => new TransportDriverResource($this->whenLoaded('driver')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
