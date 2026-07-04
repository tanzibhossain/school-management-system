<?php

namespace Tests\Feature\School;

use App\Models\User;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'currency' => 'USD', 'is_active' => true]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
    }

    private function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    public function test_every_optional_module_is_listed_disabled_by_default(): void
    {
        $response = $this->withToken($this->adminToken())
            ->getJson('/api/v2/school/modules')
            ->assertOk();

        $this->assertCount(5, $response->json('data'));
        foreach ($response->json('data') as $row) {
            $this->assertFalse($row['is_enabled']);
        }
    }

    public function test_admin_can_enable_a_module(): void
    {
        $this->withToken($this->adminToken())
            ->putJson('/api/v2/school/modules/lms', ['is_enabled' => true])
            ->assertOk()
            ->assertJsonFragment(['module' => 'lms', 'is_enabled' => true]);

        $this->assertDatabaseHas('school_module_settings', [
            'school_id' => $this->school->id,
            'module' => 'lms',
            'is_enabled' => true,
        ]);
    }

    public function test_disabled_module_blocks_access_with_403(): void
    {
        // Payroll left disabled (no ModuleSetting row created for this school).
        $this->withToken($this->adminToken())
            ->getJson('/api/v2/payroll/components')
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'This module is not enabled for your school.']);
    }

    public function test_enabling_unblocks_access(): void
    {
        $this->withToken($this->adminToken())
            ->putJson('/api/v2/school/modules/payroll', ['is_enabled' => true])
            ->assertOk();

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/payroll/components')
            ->assertOk();
    }

    public function test_unknown_module_name_is_rejected(): void
    {
        $this->withToken($this->adminToken())
            ->putJson('/api/v2/school/modules/not-a-real-module', ['is_enabled' => true])
            ->assertUnprocessable();
    }

    public function test_non_admin_cannot_toggle_modules(): void
    {
        $teacher = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $token = $teacher->createToken('test', ['teacher:*'])->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v2/school/modules/lms', ['is_enabled' => true])
            ->assertForbidden();
    }
}
