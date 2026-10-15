<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\RoutineRoom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoutineRoom */
class RoutineRoomResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capacity' => $this->capacity,
        ];
    }
}
