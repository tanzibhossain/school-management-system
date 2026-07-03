<?php

namespace Tests\Feature\Sms;

class SmsBatchHistoryTest extends SmsTestCase
{
    public function test_admin_can_list_and_view_batch_history(): void
    {
        $this->makeStudent();
        $token = $this->adminToken();

        $created = $this->withToken($token)
            ->postJson('/api/v2/sms/manual', ['body' => 'Hello', 'scope' => 'all'])
            ->assertCreated();

        $id = $created->json('data.id');

        $this->withToken($token)->getJson('/api/v2/sms/batches')->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->getJson("/api/v2/sms/batches/{$id}")->assertOk()
            ->assertJsonFragment(['status' => 'completed']);
    }

    public function test_teacher_cannot_view_batch_history(): void
    {
        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/sms/batches')
            ->assertForbidden();
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/sms/batches')->assertUnauthorized();
    }
}
