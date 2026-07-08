<?php

namespace Tests\Feature\Messaging;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ThreadMessagingTest extends MessagingTestCase
{
    public function test_direct_thread_is_deduped(): void
    {
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');

        $first = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id],
        ], $this->auth($teacher))->assertStatus(201)->json('data.id');

        $second = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'again',
        ], $this->auth($teacher))->assertStatus(201)->json('data.id');

        $this->assertEquals($first, $second);
        $this->assertDatabaseCount('message_threads', 1);
    }

    public function test_send_raises_unread_and_read_clears_it(): void
    {
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');

        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'First message',
        ], $this->auth($teacher))->json('data.id');

        // Parent has one unread; teacher (sender) has none.
        $this->getJson('/api/v2/messaging/unread-count', $this->auth($parent))
            ->assertStatus(200)->assertJson(['unread_count' => 1]);
        $this->getJson('/api/v2/messaging/unread-count', $this->auth($teacher))
            ->assertStatus(200)->assertJson(['unread_count' => 0]);

        $this->postJson("/api/v2/messaging/threads/{$threadId}/read", [], $this->auth($parent))->assertStatus(200);

        $this->getJson('/api/v2/messaging/unread-count', $this->auth($parent))
            ->assertStatus(200)->assertJson(['unread_count' => 0]);
    }

    public function test_incremental_fetch_after_id(): void
    {
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'one',
        ], $this->auth($teacher))->json('data.id');

        $firstId = $this->getJson("/api/v2/messaging/threads/{$threadId}/messages", $this->auth($teacher))
            ->json('data.0.id');

        $this->postJson("/api/v2/messaging/threads/{$threadId}/messages", ['body' => 'two'], $this->auth($teacher))->assertStatus(201);

        $newer = $this->getJson("/api/v2/messaging/threads/{$threadId}/messages?after={$firstId}", $this->auth($teacher));
        $newer->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonFragment(['body' => 'two']);
    }

    public function test_non_participant_cannot_view_thread(): void
    {
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $outsider = $this->makeUser('teacher');

        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'hi',
        ], $this->auth($teacher))->json('data.id');

        $this->getJson("/api/v2/messaging/threads/{$threadId}", $this->auth($outsider))->assertStatus(403);
    }

    public function test_sender_can_delete_own_message(): void
    {
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'deletable',
        ], $this->auth($teacher))->json('data.id');

        $msgId = $this->getJson("/api/v2/messaging/threads/{$threadId}/messages", $this->auth($teacher))->json('data.0.id');

        $this->deleteJson("/api/v2/messaging/messages/{$msgId}", [], $this->auth($teacher))->assertStatus(200);
        $this->assertSoftDeleted('messages', ['id' => $msgId]);
    }

    public function test_attachment_upload_and_download(): void
    {
        Storage::fake('minio');
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'start',
        ], $this->auth($teacher))->json('data.id');

        $send = $this->postJson("/api/v2/messaging/threads/{$threadId}/messages", [
            'body' => 'see attached',
            'attachments' => [UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf')],
        ], $this->auth($teacher));

        $send->assertStatus(201)->assertJsonPath('data.attachments.0.original_name', 'notes.pdf');

        $attachmentId = $send->json('data.attachments.0.id');
        $this->getJson("/api/v2/messaging/attachments/{$attachmentId}", $this->auth($parent))->assertStatus(200);
    }
}
