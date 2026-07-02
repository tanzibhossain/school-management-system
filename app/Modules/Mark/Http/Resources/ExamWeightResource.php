<?php

namespace App\Modules\Mark\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Mark\Models\ExamWeight */
class ExamWeightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'class_id'         => $this->class_id,
            'academic_year_id' => $this->academic_year_id,
            'exam_id'          => $this->exam_id,
            'weight_percent'   => $this->weight_percent,
        ];
    }
}
