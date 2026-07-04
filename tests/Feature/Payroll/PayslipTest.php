<?php

namespace Tests\Feature\Payroll;

use Illuminate\Support\Facades\Storage;

class PayslipTest extends PayrollTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
    }

    private function processedEntryId(): int
    {
        $components = $this->withToken($this->adminToken())
            ->getJson("/api/v2/payroll/staff/{$this->staff->id}/salary")
            ->json('data');
        $basic = collect($components)->firstWhere('name', 'Basic Salary');

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/staff/{$this->staff->id}/salary", [
                'values' => [['component_id' => $basic['component_id'], 'amount' => 20000]],
            ])->assertOk();

        $run = $this->withToken($this->adminToken())
            ->postJson('/api/v2/payroll/runs', ['month' => 7, 'year' => 2026])
            ->assertCreated();

        $processed = $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/runs/{$run->json('data.id')}/process")
            ->assertOk();

        return $processed->json('data.entries.0.id');
    }

    public function test_admin_can_generate_a_payslip(): void
    {
        $entryId = $this->processedEntryId();

        $response = $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/entries/{$entryId}/payslip")
            ->assertOk();

        $path = $response->json('data.payslip_path');
        $this->assertNotNull($path);
        $this->assertNotNull($response->json('data.payslip_generated_at'));
        Storage::disk('minio')->assertExists($path);
    }

    public function test_staff_can_view_only_their_own_payslips(): void
    {
        $entryId = $this->processedEntryId();
        $this->withToken($this->adminToken())->postJson("/api/v2/payroll/entries/{$entryId}/payslip")->assertOk();

        $this->app['auth']->forgetGuards();

        $mine = $this->withToken($this->staffToken())
            ->getJson('/api/v2/payroll/staff/me/payslips')
            ->assertOk();
        $this->assertCount(1, $mine->json('data'));

        [$otherToken] = $this->otherStaffToken();
        $this->app['auth']->forgetGuards();

        $theirs = $this->withToken($otherToken)
            ->getJson('/api/v2/payroll/staff/me/payslips')
            ->assertOk();
        $this->assertCount(0, $theirs->json('data'));
    }

    public function test_teacher_cannot_generate_payslips(): void
    {
        $entryId = $this->processedEntryId();

        // processedEntryId() leaves Sanctum's guard resolved to the admin user from its
        // last request — must reset before switching tokens, same gotcha as the test above.
        $this->app['auth']->forgetGuards();

        $this->withToken($this->staffToken())
            ->postJson("/api/v2/payroll/entries/{$entryId}/payslip")
            ->assertForbidden();
    }
}
