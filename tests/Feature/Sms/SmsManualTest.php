<?php

namespace Tests\Feature\Sms;

class SmsManualTest extends SmsTestCase
{
    public function test_admin_can_send_manual_sms_to_a_class(): void
    {
        $this->makeStudent();
        $this->makeStudent();

        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/manual', [
                'body' => 'School will be closed tomorrow.',
                'scope' => 'class',
                'class_id' => $this->class->id,
                'section_id' => $this->section->id,
            ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'completed', 'total_count' => 2])
            ->assertJsonCount(2, 'data.logs');

        foreach ($response->json('data.logs') as $log) {
            $this->assertSame('sent', $log['status']);
            $this->assertSame('gsm7', $log['encoding']);
            $this->assertSame(1, $log['segment_count']);
            $this->assertEqualsWithDelta(0.5, $log['cost'], 0.0001);
        }
    }

    public function test_single_scope_targets_the_explicit_student(): void
    {
        $one = $this->makeStudent();
        $this->makeStudent();

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/manual', [
                'body' => 'Hello',
                'scope' => 'single',
                'target_ids' => [$one->id],
            ])
            ->assertCreated()
            ->assertJsonFragment(['total_count' => 1])
            ->assertJsonCount(1, 'data.logs');
    }

    public function test_missing_guardian_phone_is_logged_as_failed_not_skipped(): void
    {
        $student = $this->makeStudentWithGuardianButNoPhone();

        $response = $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/manual', [
                'body' => 'Hello',
                'scope' => 'single',
                'target_ids' => [$student->id],
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'data.logs');

        $log = $response->json('data.logs.0');
        $this->assertSame('failed', $log['status']);
        $this->assertSame('No guardian phone number on file.', $log['error_message']);
    }

    public function test_teacher_can_send_but_accountant_cannot(): void
    {
        $this->makeStudent();

        $this->withToken($this->teacherToken())
            ->postJson('/api/v2/sms/manual', [
                'body' => 'Hello',
                'scope' => 'class',
                'class_id' => $this->class->id,
            ])
            ->assertCreated();

        // Sanctum caches the resolved user within a test — forget the guard before
        // switching tokens (see SESSION_START.md's test gotchas, first hit in Leave).
        $this->app['auth']->forgetGuards();

        $this->withToken($this->accountantToken())
            ->postJson('/api/v2/sms/manual', [
                'body' => 'Hello',
                'scope' => 'class',
                'class_id' => $this->class->id,
            ])
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/sms/manual', ['body' => 'Hello', 'scope' => 'all'])
            ->assertUnauthorized();
    }
}
