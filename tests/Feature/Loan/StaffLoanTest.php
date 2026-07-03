<?php

namespace Tests\Feature\Loan;

use App\Models\User;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffLoanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private Staff $staff;
    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->staffUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->staff     = Staff::create([
            'school_id' => $this->school->id,
            'user_id'   => $this->staffUser->id,
            'name'      => 'Staff One',
            'gender'    => 'female',
        ]);
    }

    private function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function staffToken(): string
    {
        return $this->staffUser->createToken('test', ['staff:*'])->plainTextToken;
    }

    private function accountantToken(): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $user->assignRole('accountant');

        return $user->createToken('test', ['accountant:*'])->plainTextToken;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'requested_amount'  => 6000,
            'installment_count' => 3,
            'reason'            => 'Medical expense',
            'start_date'        => '2026-02-01',
        ], $overrides);
    }

    public function test_staff_can_submit_and_admin_can_approve_generating_schedule(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertCreated()
            ->assertJsonFragment(['status' => 'pending']);

        $id = $response->json('data.id');

        // Sanctum's guard caches the resolved user within a test — reset before switching tokens
        $this->app['auth']->forgetGuards();

        $response = $this->withToken($this->adminToken())
            ->patchJson("/api/v2/loans/{$id}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        $schedules = $response->json('data.schedules');
        $this->assertCount(3, $schedules);
        $this->assertEquals(['2026-02-01', '2026-03-01', '2026-04-01'], array_column($schedules, 'due_date'));
        $this->assertEquals(6000.0, array_sum(array_column($schedules, 'amount')));

        $this->assertDatabaseCount('loan_schedules', 3);
    }

    public function test_accountant_can_approve(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($this->accountantToken())
            ->patchJson("/api/v2/loans/{$id}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);
    }

    public function test_staff_cannot_approve_own_request(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        // The approve/reject routes require admin:*/accountant:* — a plain staff:* token
        // never reaches the controller at all, rejected by the Sanctum ability middleware.
        $this->withToken($this->staffToken())
            ->patchJson("/api/v2/loans/{$id}/approve")
            ->assertForbidden();
    }

    public function test_reject_with_reason(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/loans/{$id}/reject", ['rejection_reason' => 'Insufficient tenure'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'rejected', 'rejection_reason' => 'Insufficient tenure']);

        $this->assertDatabaseCount('loan_schedules', 0);
    }

    public function test_requester_can_cancel_while_pending(): void
    {
        $token = $this->staffToken();

        $response = $this->withToken($token)
            ->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        $this->withToken($token)
            ->patchJson("/api/v2/loans/{$id}/cancel")
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_cancelling_an_approved_loan_removes_its_schedule(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/loans/{$id}/approve")
            ->assertOk();

        $this->assertDatabaseCount('loan_schedules', 3);

        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/loans/{$id}/cancel")
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelled']);

        $this->assertDatabaseCount('loan_schedules', 0);
    }

    public function test_requires_auth(): void
    {
        $this->postJson("/api/v2/loans/{$this->staff->id}", $this->payload())
            ->assertUnauthorized();
    }
}
