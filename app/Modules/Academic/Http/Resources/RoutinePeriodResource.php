<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\Academic\Models\RoutinePeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoutinePeriod */
class RoutinePeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
        ];
    }
}
