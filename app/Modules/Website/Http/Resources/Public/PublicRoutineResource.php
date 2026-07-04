<?php

namespace App\Modules\Website\Http\Resources\Public;

use App\Modules\Academic\Models\ClassRoutine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassRoutine */
class PublicRoutineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject?->name),
            'room' => $this->whenLoaded('room', fn () => $this->room?->name),
            'period' => $this->whenLoaded('period', fn () => [
                'name' => $this->period?->name,
                'start_time' => $this->period?->start_time,
                'end_time' => $this->period?->end_time,
            ]),
        ];
    }
}
