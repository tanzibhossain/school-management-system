<?php

namespace App\Modules\School\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolOpeningHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'day_of_week' => $this->day_of_week,
            'day_name' => $this->day_name,
            'is_open' => $this->is_open,
            'open_time' => $this->open_time,
            'close_time' => $this->close_time,
        ];
    }
}
