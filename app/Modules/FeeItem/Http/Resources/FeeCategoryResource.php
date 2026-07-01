<?php

namespace App\Modules\FeeItem\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\FeeItem\Models\FeeCategory */
class FeeCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'is_active' => $this->is_active,
        ];
    }
}
