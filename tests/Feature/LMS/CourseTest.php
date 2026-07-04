<?php

namespace Tests\Feature\LMS;

class CourseTest extends LMSTestCase
{
    public function test_admin_can_create_a_course(): void
    {
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/lms/courses', [
                'class_id' => $this->class->id,
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->staff->id,
                'title' => 'Geometry',
            ])
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Geometry']);
    }

    public function test_teacher_creating_a_course_is_always_assigned_as_its_own_teacher(): void
    {
        [$otherToken, $otherStaff] = $this->otherTeacherToken();

        $response = $this->withToken($otherToken)
            ->postJson('/api/v2/lms/courses', [
                'class_id' => $this->class->id,
                'subject_id' => $this->subject->id,
                // Attempting to assign someone else — ignored, overridden to self.
                'teacher_id' => $this->staff->id,
                'title' => 'Science',
            ])
            ->assertCreated();

        $this->assertSame($otherStaff->id, $response->json('data.teacher_id'));
    }

    public function test_student_sees_courses_for_their_own_class(): void
    {
        [$token] = $this->makeStudent();

        $this->withToken($token)
            ->getJson('/api/v2/lms/courses')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Algebra Basics']);
    }

    public function test_teacher_sees_own_courses_without_a_class_id(): void
    {
        $this->withToken($this->teacherToken())
            ->getJson('/api/v2/lms/courses')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Algebra Basics']);
    }

    public function test_non_owning_teacher_cannot_edit_the_course(): void
    {
        [$otherToken] = $this->otherTeacherToken();

        $this->withToken($otherToken)
            ->putJson("/api/v2/lms/courses/{$this->course->id}", ['title' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_admin_can_edit_any_course(): void
    {
        $this->withToken($this->adminToken())
            ->putJson("/api/v2/lms/courses/{$this->course->id}", ['title' => 'Advanced Algebra'])
            ->assertOk()
            ->assertJsonFragment(['title' => 'Advanced Algebra']);
    }

    public function test_requires_auth(): void
    {
        $this->getJson('/api/v2/lms/courses')->assertUnauthorized();
    }
}
