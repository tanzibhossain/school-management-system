<?php

namespace App\Modules\Examination\Http\Resources;

use App\Modules\Examination\Models\ExamHall;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExamHall */
class ExamHallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'layout_config' => $this->layout_config,
            'total_seats' => $this->total_seats,
            'available_seats_count' => $this->available_seats_count,
            'seats' => ExamHallSeatResource::collection($this->whenLoaded('seats')),
            'created_at' => $this->created_at,
        ];
    }
}
