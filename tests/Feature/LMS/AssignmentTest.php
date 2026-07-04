<?php

namespace Tests\Feature\LMS;

class AssignmentTest extends LMSTestCase
{
    public function test_teacher_can_create_an_assignment(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/courses/{$this->course->id}/assignments", [
                'title' => 'Homework 1',
                'due_date' => now()->addDays(7)->toIso8601String(),
                'max_marks' => 100,
            ])
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Homework 1', 'max_marks' => 100]);
    }

    public function test_non_owning_teacher_cannot_create_an_assignment(): void
    {
        [$otherToken] = $this->otherTeacherToken();

        $this->withToken($otherToken)
            ->postJson("/api/v2/lms/courses/{$this->course->id}/assignments", [
                'title' => 'Homework 1',
                'due_date' => now()->addDays(7)->toIso8601String(),
                'max_marks' => 100,
            ])
            ->assertForbidden();
    }

    public function test_student_can_list_assignments_for_a_course(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/courses/{$this->course->id}/assignments", [
                'title' => 'Homework 1',
                'due_date' => now()->addDays(7)->toIso8601String(),
                'max_marks' => 100,
            ])->assertCreated();

        $this->app['auth']->forgetGuards();
        [$studentToken] = $this->makeStudent();

        $this->withToken($studentToken)
            ->getJson("/api/v2/lms/courses/{$this->course->id}/assignments")
            ->assertOk()
            ->assertJsonFragment(['title' => 'Homework 1']);
    }

    public function test_admin_can_update_and_delete_an_assignment(): void
    {
        $assignment = $this->withToken($this->adminToken())
            ->postJson("/api/v2/lms/courses/{$this->course->id}/assignments", [
                'title' => 'Homework 1',
                'due_date' => now()->addDays(7)->toIso8601String(),
                'max_marks' => 100,
            ])->json('data');

        $this->withToken($this->adminToken())
            ->putJson("/api/v2/lms/assignments/{$assignment['id']}", ['max_marks' => 50])
            ->assertOk()
            ->assertJsonFragment(['max_marks' => 50]);

        $this->withToken($this->adminToken())
            ->deleteJson("/api/v2/lms/assignments/{$assignment['id']}")
            ->assertOk();

        $this->assertDatabaseMissing('lms_assignments', ['id' => $assignment['id']]);
    }
}
