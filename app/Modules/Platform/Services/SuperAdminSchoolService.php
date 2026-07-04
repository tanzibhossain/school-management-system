<?php

namespace App\Modules\Platform\Services;

use App\Modules\Platform\Models\Plan;
use App\Modules\School\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Super-Admin-only operations, gated by the `role:super_admin` middleware (a real
 * Spatie role check — NOT a Sanctum ability check, since 'admin' tokens already
 * carry a bare '*' ability that would otherwise satisfy any ability-based gate).
 * These operate across EVERY school, outside current_school_id scoping.
 */
class SuperAdminSchoolService
{
    public function __construct(
        private readonly SchoolProvisioningService $provisioning,
        private readonly PlanService $plans,
    ) {}

    /** @return Collection<int, School> */
    public function all(): Collection
    {
        return School::with('plan')->orderByDesc('created_at')->get();
    }

    /**
     * @param array{school_name: string, subdomain: string, admin_name: string, admin_email: string, country_code?: string|null} $schoolData
     */
    public function createOffline(array $schoolData, int $planId, \DateTimeInterface $subscriptionExpiresAt): School
    {
        $plan = $this->plans->findOrFail($planId);

        return $this->provisioning->provision(
            $schoolData,
            $plan,
            'offline_manual',
            subscriptionExpiresAt: $subscriptionExpiresAt,
        );
    }

    public function changePlan(School $school, int $planId, ?\DateTimeInterface $subscriptionExpiresAt = null): School
    {
        $plan = $this->plans->findOrFail($planId);

        if ($school->is_demo) {
            throw new UnprocessableEntityHttpException('The shared Demo school\'s plan cannot be changed.');
        }

        $school->update([
            'plan_id' => $plan->id,
            'subscription_expires_at' => $subscriptionExpiresAt ?? $school->subscription_expires_at,
        ]);

        return $school->fresh('plan');
    }
}
