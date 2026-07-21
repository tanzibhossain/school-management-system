<?php

namespace Tests\Feature\User;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'Test School', 'is_active' => true]);

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);

        $this->user = User::create([
            'school_id' => $this->school->id,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->user->assignRole('admin');
    }

    public function test_login_returns_token_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'expires_at', 'user' => ['id', 'email', 'role']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_locked_out_after_3_failed_attempts(): void
    {
        RateLimiter::clear('login:admin@test.com|127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v2/auth/login', [
                'email' => 'admin@test.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123', // correct — but locked
        ]);

        $response->assertStatus(429);
    }

    public function test_successful_login_clears_lockout(): void
    {
        RateLimiter::clear('login:admin@test.com|127.0.0.1');

        // 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/api/v2/auth/login', [
                'email' => 'admin@test.com',
                'password' => 'wrong-password',
            ]);
        }

        // Successful login should clear the counter
        $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ])->assertOk();

        // Should be able to attempt again without lockout
        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422); // 422 not 429
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_remember_me_sets_longer_expiry(): void
    {
        $normal = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'remember_me' => false,
        ]);

        $this->user->tokens()->delete();
        RateLimiter::clear('login:admin@test.com|127.0.0.1');

        $remember = $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'remember_me' => true,
        ]);

        $normalExpiry = $normal->json('expires_at');
        $rememberExpiry = $remember->json('expires_at');

        $this->assertNotNull($normalExpiry);
        $this->assertNotNull($rememberExpiry);
        $this->assertGreaterThan(
            strtotime($normalExpiry),
            strtotime($rememberExpiry),
        );
    }

    public function test_logout_revokes_current_token(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v2/auth/logout')
            ->assertOk();

        // The auth manager caches the resolved guard + user across requests in
        // the same test process. forgetGuards() forces a fresh DB lookup so the
        // next request correctly sees the deleted token.
        auth()->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v2/auth/me')
            ->assertUnauthorized();
    }

    public function test_logout_all_revokes_all_tokens(): void
    {
        $token1 = $this->user->createToken('device-1')->plainTextToken;
        $token2 = $this->user->createToken('device-2')->plainTextToken;

        $this->withToken($token1)
            ->postJson('/api/v2/auth/logout-all')
            ->assertOk();

        auth()->forgetGuards();

        $this->withToken($token2)
            ->getJson('/api/v2/auth/me')
            ->assertUnauthorized();
    }

    public function test_devices_lists_active_tokens(): void
    {
        $token = $this->user->createToken('My Laptop')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v2/auth/devices');

        $response->assertOk()
            ->assertJsonFragment(['device_name' => 'My Laptop']);
    }

    public function test_revoke_device_removes_specific_token(): void
    {
        $token1 = $this->user->createToken('device-1')->plainTextToken;
        $this->user->createToken('device-2');
        $tokenId = $this->user->tokens()->where('name', 'device-2')->first()->id;

        $this->withToken($token1)
            ->deleteJson("/api/v2/auth/devices/{$tokenId}")
            ->assertOk();

        $this->assertEquals(1, $this->user->tokens()->count());
    }

    public function test_me_returns_authenticated_user(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v2/auth/me')
            ->assertOk()
            ->assertJsonFragment(['email' => 'admin@test.com']);
    }

    public function test_login_history_is_recorded_on_success(): void
    {
        $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'email' => 'admin@test.com',
            'status' => 'success',
        ]);
    }

    public function test_login_history_is_recorded_on_failure(): void
    {
        $this->postJson('/api/v2/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('login_histories', [
            'email' => 'admin@test.com',
            'status' => 'failed',
        ]);
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('login:admin@test.com|127.0.0.1');
        parent::tearDown();
    }
}
