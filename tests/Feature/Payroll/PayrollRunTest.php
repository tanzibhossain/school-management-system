<?php

namespace Tests\Feature\Payroll;

use App\Modules\Loan\Models\LoanSchedule;
use App\Modules\Loan\Models\StaffLoan;

class PayrollRunTest extends PayrollTestCase
{
    private function setBasicSalary(float $amount): void
    {
        $components = $this->withToken($this->adminToken())
            ->getJson("/api/v2/payroll/staff/{$this->staff->id}/salary")
            ->json('data');
        $basic = collect($components)->firstWhere('name', 'Basic Salary');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/staff/{$this->staff->id}/salary", [
                'values' => [['component_id' => $basic['component_id'], 'amount' => $amount]],
            ])->assertOk();
    }

    public function test_admin_can_create_a_run_and_duplicate_month_year_is_rejected(): void
    {
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 7, 'year' => 2026])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'draft']);

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 7, 'year' => 2026])
            ->assertUnprocessable();
    }

    public function test_processing_calculates_gross_and_net_from_components(): void
    {
        $this->setBasicSalary(30000);

        $run = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 7, 'year' => 2026])
            ->assertCreated();
        $runId = $run->json('data.id');

        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$runId}/process")
            ->assertOk();

        $entries = $response->json('data.entries');
        $this->assertCount(1, $entries);
        $this->assertSame('30000.00', $entries[0]['gross_salary']);
        $this->assertSame('30000.00', $entries[0]['net_salary']);
        $this->assertSame('0.00', $entries[0]['total_deductions']);
    }

    public function test_reprocessing_is_idempotent(): void
    {
        $this->setBasicSalary(20000);

        $run = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 8, 'year' => 2026])
            ->assertCreated();
        $runId = $run->json('data.id');

        $this->withToken($this->adminToken())->postJson("/api/v2/payroll/runs/{$runId}/process")->assertOk();
        $this->assertDatabaseCount('payroll_entries', 1);

        $this->withToken($this->adminToken())->postJson("/api/v2/payroll/runs/{$runId}/process")->assertOk();
        $this->assertDatabaseCount('payroll_entries', 1);
    }

    public function test_approve_requires_processing_first(): void
    {
        $run = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 9, 'year' => 2026])
            ->assertCreated();

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$run->json('data.id')}/approve")
            ->assertUnprocessable();
    }

    public function test_approve_locks_the_run_and_rejects_a_second_approval(): void
    {
        $this->setBasicSalary(15000);

        $run = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 10, 'year' => 2026])
            ->assertCreated();
        $runId = $run->json('data.id');

        $this->withToken($this->adminToken())->postJson("/api/v2/payroll/runs/{$runId}/process")->assertOk();

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$runId}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$runId}/approve")
            ->assertUnprocessable();
    }

    public function test_due_loan_installment_is_deducted_and_marked_paid_on_approval(): void
    {
        $this->setBasicSalary(20000);

        $loan = StaffLoan::create([
            'school_id' => $this->school->id,
            'staff_id' => $this->staff->id,
            'requested_amount' => 6000,
            'installment_count' => 3,
            'reason' => 'Medical',
            'start_date' => '2026-11-01',
            'status' => 'approved',
            'requested_by' => $this->admin->id,
        ]);
        $schedule = LoanSchedule::create([
            'school_id' => $this->school->id,
            'staff_loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-11-15',
            'amount' => 2000,
        ]);

        $run = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 11, 'year' => 2026])
            ->assertCreated();
        $runId = $run->json('data.id');

        $processed = $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$runId}/process")
            ->assertOk();

        $entry = $processed->json('data.entries.0');
        $this->assertSame('2000.00', $entry['total_deductions']);
        $this->assertSame('18000.00', $entry['net_salary']);
        $this->assertTrue(collect($entry['breakdown'])->contains(fn ($line) => $line['type'] === 'loan_deduction' && $line['loan_schedule_id'] === $schedule->id));

        $this->assertDatabaseHas('loan_schedules', ['id' => $schedule->id, 'is_paid' => false]);

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$runId}/approve")
            ->assertOk();

        $this->assertDatabaseHas('loan_schedules', ['id' => $schedule->id, 'is_paid' => true, 'paid_amount' => 2000]);
    }

    public function test_teacher_cannot_manage_runs(): void
    {
        $this->withToken($this->staffToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 12, 'year' => 2026])
            ->assertForbidden();
    }
}
