<?php

namespace App\Modules\Mark\Http\Resources;

use App\Modules\Mark\Models\MarkDivision;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MarkDivision */
class MarkDivisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'exam_subject_id' => $this->exam_subject_id,
            'name' => $this->name,
            'max_marks' => $this->max_marks,
            'pass_mark' => $this->pass_mark,
            'display_order' => $this->display_order,
        ];
    }
}
