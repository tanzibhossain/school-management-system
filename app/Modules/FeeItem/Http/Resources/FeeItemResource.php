<?php

namespace App\Modules\FeeItem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\FeeItem\Models\FeeItem */
class FeeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'category'         => new FeeCategoryResource($this->whenLoaded('category')),
            'academic_year_id' => $this->academic_year_id,
            'class_id'         => $this->class_id,
            'name'             => $this->name,
            'amount'           => $this->amount,
            'frequency'        => $this->frequency,
            'due_day'          => $this->due_day,
            'is_mandatory'     => $this->is_mandatory,
            'is_active'        => $this->is_active,
            'created_at'       => $this->created_at->toIso8601String(),
        ];
    }
}
