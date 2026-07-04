<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\School\Models\School;

class DemoLoginTest extends PlatformTestCase
{
    public function test_demo_login_returns_prefilled_credentials(): void
    {
        $demo = School::create(['name' => 'Demo School', 'subdomain' => 'demo', 'is_demo' => true, 'plan_id' => $this->demoPlan->id, 'is_active' => true]);
        $admin = User::factory()->create(['school_id' => $demo->id, 'is_active' => true, 'email' => 'admin@demo.example']);
        $admin->assignRole('admin');

        $this->getJson('/api/v2/platform/demo')
            ->assertOk()
            ->assertJsonFragment([
                'school_name' => 'Demo School',
                'subdomain' => 'demo',
                'login_email' => 'admin@demo.example',
                'resets_every_hours' => 14,
            ]);
    }

    public function test_returns_404_when_no_demo_school_exists(): void
    {
        $this->getJson('/api/v2/platform/demo')->assertNotFound();
    }
}
