<?php

namespace App\Modules\Mark\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Mark\Models\MarkSetting */
class MarkSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'class_id'            => $this->class_id,
            'mode'                => $this->mode,
            'result_strategy'     => $this->result_strategy,
            'show_merit_position' => $this->show_merit_position,
            'grace_marks_cap'     => $this->grace_marks_cap,
        ];
    }
}
