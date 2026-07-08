<?php

namespace App\Modules\Transport\Http\Resources;

use App\Modules\Transport\Models\StudentTransportAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StudentTransportAssignment */
class StudentTransportAssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'transport_route_id' => $this->transport_route_id,
            'pickup_point' => $this->pickup_point,
            'starts_on' => $this->starts_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'status' => $this->status,
            'is_expired' => $this->status === 'active'
                && $this->ends_on !== null
                && $this->ends_on->isPast(),
            'route' => new TransportRouteResource($this->whenLoaded('route')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
