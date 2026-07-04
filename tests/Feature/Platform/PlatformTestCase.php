<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Platform\Models\Plan;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PlatformTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    /** A regular (non-platform) school + its admin — proves super_admin routes reject a normal admin. */
    protected School $tenantSchool;
    protected User $tenantAdmin;

    protected Plan $demoPlan;
    protected Plan $trialPlan;
    protected Plan $basicPlan;
    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        foreach (config('platform.seed_plans') as $planData) {
            $plan = Plan::create($planData);
            match ($plan->slug) {
                'demo' => $this->demoPlan = $plan,
                'trial' => $this->trialPlan = $plan,
                'basic' => $this->basicPlan = $plan,
                'pro' => $this->proPlan = $plan,
                default => null,
            };
        }

        // super_admin has NO school_id — platform-level operator.
        $this->superAdmin = User::factory()->create(['school_id' => null, 'is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->tenantSchool = School::create(['name' => 'Existing School', 'is_active' => true]);
        $this->tenantAdmin = User::factory()->create(['school_id' => $this->tenantSchool->id, 'is_active' => true]);
        $this->tenantAdmin->assignRole('admin');
    }

    protected function superAdminToken(): string
    {
        return $this->superAdmin->createToken('test', ['super_admin:*'])->plainTextToken;
    }

    /** A REGULAR admin token — 'admin' still carries the bare '*' ability, which is exactly why role:super_admin (not ability:) gates the Super Admin portal. */
    protected function tenantAdminToken(): string
    {
        return $this->tenantAdmin->createToken('test', ['*'])->plainTextToken;
    }
}
