<?php

namespace Tests\Feature\Platform;

use App\Modules\School\Models\School;
use Illuminate\Support\Facades\Mail;

class SuperAdminSchoolTest extends PlatformTestCase
{
    public function test_super_admin_can_create_an_offline_school(): void
    {
        Mail::fake();

        $response = $this->withToken($this->superAdminToken())
            ->postJson('/api/v2/platform/admin/schools', [
                'school_name' => 'Offline School',
                'subdomain' => 'offlineschool',
                'admin_name' => 'Offline Admin',
                'admin_email' => 'offline@example.com',
                'plan_id' => $this->proPlan->id,
                'subscription_expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertCreated();

        $response->assertJsonFragment(['provisioning_type' => 'offline_manual']);

        $this->assertDatabaseHas('schools', [
            'subdomain' => 'offlineschool',
            'provisioning_type' => 'offline_manual',
        ]);
    }

    public function test_regular_admin_token_is_forbidden_from_super_admin_routes(): void
    {
        // This is the critical regression test: 'admin' role tokens carry a bare
        // '*' Sanctum ability, which would satisfy ANY ability-based middleware
        // check. role:super_admin checks the actual Spatie role instead, so a
        // normal school admin must be rejected here.
        $this->withToken($this->tenantAdminToken())
            ->getJson('/api/v2/platform/admin/schools')
            ->assertForbidden();

        $this->withToken($this->tenantAdminToken())
            ->postJson('/api/v2/platform/admin/schools', [
                'school_name' => 'Hijack', 'subdomain' => 'hijack', 'admin_name' => 'X',
                'admin_email' => 'x@example.com', 'plan_id' => $this->proPlan->id,
                'subscription_expires_at' => now()->addYear()->toDateString(),
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_is_rejected(): void
    {
        $this->getJson('/api/v2/platform/admin/schools')->assertUnauthorized();
    }

    public function test_super_admin_can_list_all_schools(): void
    {
        $this->withToken($this->superAdminToken())
            ->getJson('/api/v2/platform/admin/schools')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Existing School']);
    }

    public function test_super_admin_can_change_a_schools_plan(): void
    {
        $this->withToken($this->superAdminToken())
            ->patchJson("/api/v2/platform/admin/schools/{$this->tenantSchool->id}/plan", [
                'plan_id' => $this->basicPlan->id,
            ])
            ->assertOk()
            ->assertJsonFragment(['slug' => 'basic']);

        $this->assertDatabaseHas('schools', ['id' => $this->tenantSchool->id, 'plan_id' => $this->basicPlan->id]);
    }

    public function test_demo_schools_plan_cannot_be_changed(): void
    {
        $demoSchool = School::create(['name' => 'Demo', 'is_demo' => true, 'plan_id' => $this->demoPlan->id, 'is_active' => true]);

        $this->withToken($this->superAdminToken())
            ->patchJson("/api/v2/platform/admin/schools/{$demoSchool->id}/plan", ['plan_id' => $this->proPlan->id])
            ->assertUnprocessable();
    }

    public function test_super_admin_can_manage_plans(): void
    {
        $this->withToken($this->superAdminToken())
            ->postJson('/api/v2/platform/admin/plans', [
                'name' => 'Enterprise', 'slug' => 'enterprise', 'is_self_serve' => false,
            ])
            ->assertCreated()
            ->assertJsonFragment(['slug' => 'enterprise']);

        $this->assertDatabaseHas('plans', ['slug' => 'enterprise']);
    }
}
