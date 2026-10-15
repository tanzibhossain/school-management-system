<?php

namespace App\Modules\Examination\Http\Resources;

use App\Modules\Examination\Models\ExamHallSeat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ExamHallSeat */
class ExamHallSeatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hall_id' => $this->hall_id,
            'row' => $this->row,
            'side' => $this->side,
            'position' => $this->position,
            'label' => $this->label,
            'is_available' => $this->is_available,
        ];
    }
}
