<?php

namespace Tests\Feature\Messaging;

use App\Modules\School\Models\ModuleSetting;

class ModerationTest extends MessagingTestCase
{
    public function test_admin_can_list_all_threads_and_read_any(): void
    {
        $admin = $this->makeUser('admin');
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');

        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'private note',
        ], $this->auth($teacher))->json('data.id');

        $this->getJson('/api/v2/messaging/admin/threads', $this->auth($admin))
            ->assertStatus(200)->assertJsonCount(1, 'data');

        $this->getJson("/api/v2/messaging/admin/threads/{$threadId}/messages", $this->auth($admin))
            ->assertStatus(200)->assertJsonFragment(['body' => 'private note']);
    }

    public function test_locking_a_thread_blocks_new_messages(): void
    {
        $admin = $this->makeUser('admin');
        $teacher = $this->makeUser('teacher');
        $parent = $this->makeUser('parent');
        $threadId = $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$parent->id], 'body' => 'hi',
        ], $this->auth($teacher))->json('data.id');

        $this->postJson("/api/v2/messaging/admin/threads/{$threadId}/lock", ['locked' => true], $this->auth($admin))
            ->assertStatus(200)->assertJsonFragment(['is_locked' => true]);

        $this->postJson("/api/v2/messaging/threads/{$threadId}/messages", ['body' => 'blocked?'], $this->auth($teacher))
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_access_moderation(): void
    {
        $teacher = $this->makeUser('teacher');

        $this->getJson('/api/v2/messaging/admin/threads', $this->auth($teacher))->assertStatus(403);
    }

    public function test_requires_module_enabled_and_auth(): void
    {
        $teacher = $this->makeUser('teacher');
        $this->getJson('/api/v2/messaging/threads')->assertStatus(401);

        ModuleSetting::where('school_id', $this->school->id)
            ->where('module', 'messaging')->delete();
        $this->getJson('/api/v2/messaging/threads', $this->auth($teacher))->assertStatus(403);
    }
}
