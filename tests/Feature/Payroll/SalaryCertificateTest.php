<?php

namespace Tests\Feature\Payroll;

use Illuminate\Support\Facades\Storage;

class SalaryCertificateTest extends PayrollTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');
    }

    public function test_staff_can_request_own_certificate(): void
    {
        $this->withToken($this->staffToken())
            ->postJson('/api/v2/payroll/salary-certificate', ['purpose' => 'Bank loan application'])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'pending', 'purpose' => 'Bank loan application']);
    }

    public function test_admin_can_list_pending_and_generate(): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($this->staffToken())
            ->postJson('/api/v2/payroll/salary-certificate', ['purpose' => 'Visa application'])
            ->assertCreated();

        $this->app['auth']->forgetGuards();
        $pending = $this->withToken($this->adminToken())
            ->getJson('/api/v2/payroll/salary-certificate')
            ->assertOk();
        $this->assertCount(1, $pending->json('data'));
        $id = $pending->json('data.0.id');

        $generated = $this->withToken($this->adminToken())
            ->postJson("/api/v2/payroll/salary-certificate/{$id}/generate")
            ->assertOk()
            ->assertJsonFragment(['status' => 'generated']);

        Storage::disk('minio')->assertExists($generated->json('data.certificate_path'));
    }

    public function test_staff_sees_only_their_own_certificate_history(): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($this->staffToken())
            ->postJson('/api/v2/payroll/salary-certificate', ['purpose' => 'Bank loan'])
            ->assertCreated();

        [$otherToken] = $this->otherStaffToken();
        $this->app['auth']->forgetGuards();
        $this->withToken($otherToken)
            ->postJson('/api/v2/payroll/salary-certificate', ['purpose' => 'Visa'])
            ->assertCreated();

        $this->app['auth']->forgetGuards();
        $mine = $this->withToken($this->staffToken())
            ->getJson('/api/v2/payroll/staff/me/certificates')
            ->assertOk();

        $this->assertCount(1, $mine->json('data'));
        $this->assertSame('Bank loan', $mine->json('data.0.purpose'));
    }

    public function test_teacher_cannot_list_or_generate(): void
    {
        $this->withToken($this->staffToken())
            ->getJson('/api/v2/payroll/salary-certificate')
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/payroll/salary-certificate', ['purpose' => 'X'])->assertUnauthorized();
    }
}
