<?php

namespace Tests\Feature\Leave;

class LeaveTypeTest extends LeaveTestCase
{
    public function test_admin_can_create_and_list_leave_types(): void
    {
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/leave/types', [
                'name'                => 'Maternity Leave',
                'applies_to'          => 'staff',
                'max_days_per_year'   => 90,
                'requires_attachment' => true,
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Maternity Leave', 'applies_to' => 'staff']);

        $this->withToken($this->adminToken())
            ->getJson('/api/v2/leave/types')
            ->assertOk()
            ->assertJsonCount(3, 'data'); // 2 seeded in LeaveTestCase + this one
    }

    public function test_index_filters_by_applies_to(): void
    {
        $this->withToken($this->adminToken())
            ->getJson('/api/v2/leave/types?applies_to=staff')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Casual Leave']);
    }

    public function test_non_admin_cannot_manage_leave_types(): void
    {
        $teacherToken = $this->teacherToken();

        $this->withToken($teacherToken)
            ->postJson('/api/v2/leave/types', ['name' => 'Bereavement Leave'])
            ->assertForbidden();
    }

    public function test_admin_can_update_and_delete(): void
    {
        $this->withToken($this->adminToken())
            ->putJson("/api/v2/leave/types/{$this->studentLeaveType->id}", ['max_days_per_year' => 5])
            ->assertOk()
            ->assertJsonFragment(['max_days_per_year' => 5]);

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v2/leave/types/{$this->studentLeaveType->id}")
            ->assertOk();

        $this->assertDatabaseMissing('leave_types', ['id' => $this->studentLeaveType->id]);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/leave/types')->assertUnauthorized();
    }
}
