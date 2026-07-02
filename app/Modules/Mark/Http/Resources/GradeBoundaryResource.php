<?php

namespace App\Modules\Mark\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Mark\Models\GradeBoundary */
class GradeBoundaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'class_id'    => $this->class_id,
            'grade_label' => $this->grade_label,
            'min_percent' => $this->min_percent,
            'max_percent' => $this->max_percent,
            'gpa_point'   => $this->gpa_point,
        ];
    }
}
