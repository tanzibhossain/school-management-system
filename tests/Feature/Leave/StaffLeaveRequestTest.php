<?php

namespace Tests\Feature\Leave;

class StaffLeaveRequestTest extends LeaveTestCase
{
    private const FROM = '2026-01-05'; // Mon

    private const TO = '2026-01-06'; // Tue — 2 working days by default (fail-open)

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'leave_type_id' => $this->staffLeaveType->id,
            'from_date'     => self::FROM,
            'to_date'       => self::TO,
            'reason'        => 'Personal matter',
        ], $overrides);
    }

    public function test_staff_can_submit_and_admin_can_approve(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/leave/staff/{$this->staff->id}", $this->payload())
            ->assertCreated()
            ->assertJsonFragment(['working_days' => 2, 'status' => 'pending']);

        $id = $response->json('data.id');

        // Sanctum's guard caches the resolved user within a test — reset before switching tokens
        $this->app['auth']->forgetGuards();

        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/leave/staff/{$id}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);
    }

    public function test_non_admin_staff_cannot_approve(): void
    {
        $response = $this->withToken($this->staffToken())
            ->postJson("/api/v2/leave/staff/{$this->staff->id}", $this->payload())
            ->assertCreated();

        $id = $response->json('data.id');

        // Approve/reject/pending routes require the admin:* ability — a plain staff:* token is rejected
        // by the Sanctum ability middleware before it ever reaches StaffLeaveService's own admin-only guard.
        $this->withToken($this->staffToken())
            ->patchJson("/api/v2/leave/staff/{$id}/approve")
            ->assertForbidden();
    }

    public function test_balance_enforced_across_calendar_year(): void
    {
        $this->staffLeaveType->update(['max_days_per_year' => 2]);

        $first = $this->withToken($this->staffToken())
            ->postJson("/api/v2/leave/staff/{$this->staff->id}", $this->payload())
            ->assertCreated()
            ->json('data.id');

        // Sanctum's guard caches the resolved user within a test — reset before every token switch
        $this->app['auth']->forgetGuards();

        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/leave/staff/{$first}/approve")
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withToken($this->staffToken())
            ->postJson("/api/v2/leave/staff/{$this->staff->id}", $this->payload([
                'from_date' => '2026-01-08', 'to_date' => '2026-01-09',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('leave_type_id');
    }

    public function test_requires_auth(): void
    {
        $this->postJson("/api/v2/leave/staff/{$this->staff->id}", $this->payload())
            ->assertUnauthorized();
    }
}
