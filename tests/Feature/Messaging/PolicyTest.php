<?php

namespace Tests\Feature\Messaging;

class PolicyTest extends MessagingTestCase
{
    public function test_parent_can_message_a_teacher(): void
    {
        $parent = $this->makeUser('parent');
        $teacher = $this->makeUser('teacher');

        $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$teacher->id], 'body' => 'Hello teacher',
        ], $this->auth($parent))->assertStatus(201);
    }

    public function test_parent_cannot_message_another_parent(): void
    {
        $parent = $this->makeUser('parent');
        $other = $this->makeUser('parent');

        $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$other->id], 'body' => 'Hi',
        ], $this->auth($parent))->assertStatus(422);
    }

    public function test_student_cannot_message_another_student(): void
    {
        $a = $this->makeUser('student');
        $b = $this->makeUser('student');

        $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$b->id], 'body' => 'yo',
        ], $this->auth($a))->assertStatus(422);
    }

    public function test_staff_can_create_a_group_with_two_guardians(): void
    {
        $teacher = $this->makeUser('teacher');
        $p1 = $this->makeUser('parent');
        $p2 = $this->makeUser('parent');

        $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$p1->id, $p2->id], 'subject' => 'Class trip', 'body' => 'Details inside',
        ], $this->auth($teacher))->assertStatus(201)
            ->assertJsonFragment(['type' => 'group', 'subject' => 'Class trip']);
    }

    public function test_parent_cannot_create_a_staffless_group(): void
    {
        $parent = $this->makeUser('parent');
        $p2 = $this->makeUser('parent');
        $p3 = $this->makeUser('parent');

        $this->postJson('/api/v2/messaging/threads', [
            'participant_ids' => [$p2->id, $p3->id], 'subject' => 'Parents only', 'body' => 'x',
        ], $this->auth($parent))->assertStatus(422);
    }
}
