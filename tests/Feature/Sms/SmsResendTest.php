<?php

namespace Tests\Feature\Sms;

use App\Modules\Student\Models\StudentGuardian;

class SmsResendTest extends SmsTestCase
{
    public function test_admin_can_resend_a_failed_log_after_the_phone_is_added(): void
    {
        $student = $this->makeStudentWithGuardianButNoPhone();
        $token = $this->adminToken();

        $sendResponse = $this->withToken($token)
            ->postJson('/api/v2/sms/manual', [
                'body' => 'Hello',
                'scope' => 'single',
                'target_ids' => [$student->id],
            ])
            ->assertCreated();

        $originalLogId = $sendResponse->json('data.logs.0.id');
        $this->assertSame('failed', $sendResponse->json('data.logs.0.status'));

        StudentGuardian::where('student_id', $student->id)->update(['phone' => '+8801711111111']);

        // A resend creates a brand-new SmsLog row rather than mutating the
        // original (see SmsBatchService::resend docblock), so the freshly
        // created model triggers Laravel's automatic 201 status via
        // ResourceResponse::calculateStatus() — same as any other create.
        $resendResponse = $this->withToken($token)
            ->postJson("/api/v2/sms/logs/{$originalLogId}/resend")
            ->assertCreated();

        $this->assertSame('sent', $resendResponse->json('data.status'));
        $this->assertSame($originalLogId, $resendResponse->json('data.resent_from_id'));
        $this->assertNotSame($originalLogId, $resendResponse->json('data.id'));
    }

    public function test_non_admin_cannot_resend(): void
    {
        $student = $this->makeStudentWithGuardianButNoPhone();

        $sendResponse = $this->withToken($this->adminToken())
            ->postJson('/api/v2/sms/manual', [
                'body' => 'Hello',
                'scope' => 'single',
                'target_ids' => [$student->id],
            ])
            ->assertCreated();

        $logId = $sendResponse->json('data.logs.0.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/sms/logs/{$logId}/resend")
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/sms/logs/1/resend')->assertUnauthorized();
    }
}
