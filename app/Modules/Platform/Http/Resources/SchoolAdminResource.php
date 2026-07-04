<?php

namespace App\Modules\Platform\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Super Admin's cross-school view — deliberately separate from the tenant's own
 * SchoolResource (School module), which never exposes plan/subscription/Stripe
 * fields to a regular school admin.
 *
 * @mixin \App\Modules\School\Models\School
 */
class SchoolAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'country_code' => $this->country_code,
            'is_active' => $this->is_active,
            'is_demo' => $this->is_demo,
            'provisioning_type' => $this->provisioning_type,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'subscription_expires_at' => $this->subscription_expires_at?->toIso8601String(),
            'subscription_status' => $this->subscription_status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
