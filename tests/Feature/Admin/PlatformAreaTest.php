<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Platform\Models\Plan;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Blade admin — Super Admin portal (Platform module #23): schools list +
 * offline provisioning, plan catalogue, plan changes. role:super_admin gated.
 */
class PlatformAreaTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Plan $basic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $home = School::create([
            'name' => 'HQ', 'is_active' => true, 'currency' => 'USD',
            'timezone' => 'UTC', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->superAdmin = User::factory()->create(['school_id' => $home->id, 'is_active' => true]);
        $this->superAdmin->assignRole('super_admin');

        $this->basic = Plan::create([
            'name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 19, 'price_yearly' => 190,
            'currency' => 'USD', 'max_students' => 500, 'max_staff' => 40, 'is_self_serve' => true, 'sort_order' => 2,
        ]);
    }

    public function test_screens_load(): void
    {
        $this->actingAs($this->superAdmin);
        $this->get('/platform/schools')->assertOk();
        $this->get('/platform/schools/create')->assertOk();
        $this->get('/platform/plans')->assertOk()->assertSee('Basic');
        $this->get('/platform/signups')->assertOk();
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['school_id' => $this->superAdmin->school_id, 'is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin);
        $this->get('/platform/schools')->assertForbidden();
    }

    public function test_offline_provisioning_creates_school_and_admin(): void
    {
        Mail::fake();
        $this->actingAs($this->superAdmin);

        $this->post('/platform/schools', [
            'school_name' => 'Green Valley', 'subdomain' => 'greenvalley',
            'admin_name' => 'Head Master', 'admin_email' => 'head@greenvalley.test',
            'country_code' => 'BD', 'plan_id' => $this->basic->id,
            'subscription_expires_at' => now()->addYear()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('schools', [
            'name' => 'Green Valley', 'subdomain' => 'greenvalley',
            'plan_id' => $this->basic->id, 'provisioning_type' => 'offline_manual',
        ]);
        $this->assertDatabaseHas('users', ['email' => 'head@greenvalley.test']);
    }

    public function test_create_and_update_plan(): void
    {
        $this->actingAs($this->superAdmin);

        $this->post('/platform/plans', [
            'name' => 'Enterprise', 'slug' => 'enterprise', 'currency' => 'USD',
            'is_self_serve' => '0', 'is_active' => '1', 'sort_order' => 5,
        ])->assertRedirect();
        $this->assertDatabaseHas('plans', ['slug' => 'enterprise', 'max_students' => null]);

        $this->put("/platform/plans/{$this->basic->id}", [
            'name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 25, 'currency' => 'USD',
            'max_students' => 600, 'max_staff' => 50, 'is_self_serve' => '1', 'is_active' => '1', 'sort_order' => 2,
        ])->assertRedirect();
        $this->assertDatabaseHas('plans', ['id' => $this->basic->id, 'max_students' => 600]);
    }

    public function test_change_school_plan(): void
    {
        $school = School::create([
            'name' => 'Riverside', 'subdomain' => 'riverside', 'is_active' => true, 'currency' => 'USD',
            'timezone' => 'UTC', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
            'plan_id' => $this->basic->id, 'provisioning_type' => 'offline_manual',
        ]);
        $pro = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'is_self_serve' => true, 'sort_order' => 3]);

        $this->actingAs($this->superAdmin);
        $this->patch("/platform/schools/{$school->id}/plan", [
            'plan_id' => $pro->id,
            'subscription_expires_at' => now()->addYear()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('schools', ['id' => $school->id, 'plan_id' => $pro->id]);
    }
}
