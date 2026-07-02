<?php

namespace App\Modules\Attendance\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Attendance\Models\Holiday */
class HolidayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'date' => $this->date->toDateString(),
            'name' => $this->name,
            'type' => $this->type,
        ];
    }
}
