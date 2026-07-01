<?php

namespace App\Modules\FeeItem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\FeeItem\Models\FeeDiscount */
class FeeDiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'type'       => $this->type,
            'value'      => $this->value,
            'max_amount' => $this->max_amount,
            'is_active'  => $this->is_active,
        ];
    }
}
