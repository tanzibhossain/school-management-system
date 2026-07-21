<?php

namespace Tests\Feature\Messaging;

use App\Models\User;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class MessagingTestCase extends TestCase
{
    use RefreshDatabase;

    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Messaging Test School',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'is_active' => true,
        ]);

        ModuleSetting::create([
            'school_id' => $this->school->id,
            'module' => 'messaging',
            'is_enabled' => true,
        ]);
    }

    protected function makeUser(string $role): User
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    protected function auth(User $user): array
    {
        // Sanctum caches the resolved user on the guard across requests within a
        // single test; forget it so each request re-resolves from its own token
        // (these tests deliberately switch between teacher/parent/admin/outsider).
        $this->app['auth']->forgetGuards();

        return ['Authorization' => 'Bearer '.$user->createToken('test', ['*'])->plainTextToken];
    }
}
