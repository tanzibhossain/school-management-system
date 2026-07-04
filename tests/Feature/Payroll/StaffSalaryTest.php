<?php

namespace Tests\Feature\Payroll;

class StaffSalaryTest extends PayrollTestCase
{
    public function test_breakdown_shows_zero_for_unset_components(): void
    {
        $response = $this->withToken($this->adminToken())
            ->getJson("/api/v2/payroll/staff/{$this->staff->id}/salary")
            ->assertOk();

        $this->assertCount(7, $response->json('data'));
        foreach ($response->json('data') as $row) {
            $this->assertSame('0.00', $row['amount']);
        }
    }

    public function test_admin_can_set_values_and_breakdown_reflects_them(): void
    {
        $components = $this->withToken($this->adminToken())
            ->getJson("/api/v2/payroll/staff/{$this->staff->id}/salary")
            ->json('data');

        $basic = collect($components)->firstWhere('name', 'Basic Salary');

        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/staff/{$this->staff->id}/salary", [
                'values' => [['component_id' => $basic['component_id'], 'amount' => 30000]],
            ])
            ->assertOk();

        $updated = collect($response->json('data'))->firstWhere('name', 'Basic Salary');
        $this->assertSame('30000.00', $updated['amount']);
    }

    public function test_adding_a_new_component_does_not_affect_existing_staff_salary_values(): void
    {
        $components = $this->withToken($this->adminToken())
            ->getJson("/api/v2/payroll/staff/{$this->staff->id}/salary")
            ->json('data');
        $basic = collect($components)->firstWhere('name', 'Basic Salary');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/staff/{$this->staff->id}/salary", [
                'values' => [['component_id' => $basic['component_id'], 'amount' => 25000]],
            ])->assertOk();

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/components', ['name' => 'New Allowance', 'component_type' => 'earning'])
            ->assertCreated();

        $response = $this->withToken($this->adminToken())
            ->getJson("/api/v2/payroll/staff/{$this->staff->id}/salary")
            ->assertOk();

        $basicAfter = collect($response->json('data'))->firstWhere('name', 'Basic Salary');
        $newAllowance = collect($response->json('data'))->firstWhere('name', 'New Allowance');

        $this->assertSame('25000.00', $basicAfter['amount']);
        $this->assertSame('0.00', $newAllowance['amount']);
    }

    public function test_teacher_cannot_set_salary_values(): void
    {
        $this->withToken($this->staffToken())
            ->postJson("/api/v2/payroll/staff/{$this->staff->id}/salary", ['values' => [['component_id' => 1, 'amount' => 1000]]])
            ->assertForbidden();
    }
}
