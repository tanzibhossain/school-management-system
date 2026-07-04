<?php

namespace Tests\Feature\Payroll;

class SalaryComponentTest extends PayrollTestCase
{
    public function test_index_lazily_seeds_default_components(): void
    {
        $this->assertDatabaseCount('salary_components', 0);

        $response = $this->withToken($this->adminToken())
            ->getJson('/api/v2/payroll/components')
            ->assertOk();

        $this->assertCount(7, $response->json('data'));
        $this->assertDatabaseHas('salary_components', ['name' => 'Basic Salary', 'component_type' => 'earning', 'is_default' => true]);
        $this->assertDatabaseHas('salary_components', ['name' => 'Income Tax', 'component_type' => 'deduction', 'is_default' => true]);
    }

    public function test_admin_can_add_a_custom_component(): void
    {
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/components', ['name' => 'Transport Allowance', 'component_type' => 'earning'])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Transport Allowance', 'is_default' => false]);
    }

    public function test_accountant_can_rename_and_reorder(): void
    {
        $component = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/components', ['name' => 'Bonus', 'component_type' => 'earning'])
            ->assertCreated();
        $id = $component->json('data.id');

        $this->withToken($this->accountantToken())
            ->putJson("/api/v2/payroll/components/{$id}", ['name' => 'Annual Bonus', 'sort_order' => 99])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Annual Bonus', 'sort_order' => 99]);
    }

    public function test_admin_can_trash_a_component_and_it_disappears_from_the_active_list(): void
    {
        $response = $this->withToken($this->adminToken())->getJson('/api/v2/payroll/components')->assertOk();
        $id = $response->json('data.0.id');

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v2/payroll/components/{$id}")
            ->assertOk()
            ->assertJsonFragment(['is_trash' => true]);

        $this->assertDatabaseHas('salary_components', ['id' => $id, 'is_trash' => true]);

        $active = $this->withToken($this->adminToken())->getJson('/api/v2/payroll/components')->assertOk();
        $this->assertCount(6, $active->json('data'));
    }

    public function test_teacher_cannot_manage_components(): void
    {
        $this->withToken($this->staffToken())
            ->postJson('/api/v2/payroll/components', ['name' => 'X', 'component_type' => 'earning'])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/payroll/components')->assertUnauthorized();
    }
}
