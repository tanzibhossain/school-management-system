<?php

namespace App\Modules\Platform\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Platform\Models\Plan */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price_monthly' => $this->price_monthly,
            'price_yearly' => $this->price_yearly,
            'currency' => $this->currency,
            'max_students' => $this->max_students,
            'max_staff' => $this->max_staff,
            'trial_days' => $this->trial_days,
            'is_self_serve' => $this->is_self_serve,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
