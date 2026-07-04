<?php

namespace Tests\Feature\LMS;

class LessonTest extends LMSTestCase
{
    public function test_teacher_can_create_a_text_lesson(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/courses/{$this->course->id}/lessons", [
                'title' => 'Introduction',
                'content_type' => 'text',
                'body_text' => 'Welcome to the course.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Introduction', 'is_published' => false]);
    }

    public function test_unpublished_lesson_is_hidden_from_students(): void
    {
        $lesson = $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/courses/{$this->course->id}/lessons", [
                'title' => 'Draft Lesson',
                'content_type' => 'text',
                'body_text' => 'Not ready yet.',
            ])->json('data');

        $this->app['auth']->forgetGuards();
        [$studentToken] = $this->makeStudent();

        $this->withToken($studentToken)
            ->getJson("/api/v2/lms/lessons/{$lesson['id']}")
            ->assertNotFound();

        $this->withToken($studentToken)
            ->getJson("/api/v2/lms/courses/{$this->course->id}/lessons")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_publishing_makes_it_visible_to_students(): void
    {
        $lesson = $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/courses/{$this->course->id}/lessons", [
                'title' => 'Published Lesson',
                'content_type' => 'text',
                'body_text' => 'Ready.',
            ])->json('data');

        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/lessons/{$lesson['id']}/publish")
            ->assertOk()
            ->assertJsonFragment(['is_published' => true]);

        $this->app['auth']->forgetGuards();
        [$studentToken] = $this->makeStudent();

        $this->withToken($studentToken)
            ->getJson("/api/v2/lms/lessons/{$lesson['id']}")
            ->assertOk()
            ->assertJsonFragment(['title' => 'Published Lesson']);
    }

    public function test_non_owning_teacher_cannot_add_a_lesson(): void
    {
        [$otherToken] = $this->otherTeacherToken();

        $this->withToken($otherToken)
            ->postJson("/api/v2/lms/courses/{$this->course->id}/lessons", [
                'title' => 'Intrusion',
                'content_type' => 'text',
                'body_text' => 'Nope.',
            ])
            ->assertForbidden();
    }
}
