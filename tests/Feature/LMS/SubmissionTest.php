<?php

namespace Tests\Feature\LMS;

use App\Modules\LMS\Models\Assignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SubmissionTest extends LMSTestCase
{
    private function makeAssignment(array $overrides = []): Assignment
    {
        return Assignment::create(array_merge([
            'school_id' => $this->school->id,
            'course_id' => $this->course->id,
            'title' => 'Essay',
            'due_date' => now()->addDays(3),
            'max_marks' => 100,
            'allow_late_submission' => true,
        ], $overrides));
    }

    private function anthropicSuccessFake(int $score = 12, bool $likelyAi = false): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'id' => 'msg_test',
                'content' => [
                    ['type' => 'text', 'text' => json_encode([
                        'ai_score' => $score,
                        'likely_ai_generated' => $likelyAi,
                        'originality_note' => 'Looks like original student work.',
                    ])],
                ],
            ], 200),
        ]);
    }

    public function test_student_can_submit_before_due_date(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake();
        $assignment = $this->makeAssignment();
        [$token] = $this->makeStudent();

        $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'My original essay.'),
            ])
            ->assertCreated()
            ->assertJsonFragment(['late_submission' => false]);
    }

    public function test_late_submission_is_flagged_when_allowed(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake();
        $assignment = $this->makeAssignment(['due_date' => now()->subDay(), 'allow_late_submission' => true]);
        [$token] = $this->makeStudent();

        $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Late but allowed.'),
            ])
            ->assertCreated()
            ->assertJsonFragment(['late_submission' => true]);
    }

    public function test_late_submission_rejected_when_not_allowed(): void
    {
        Storage::fake('minio');
        $assignment = $this->makeAssignment(['due_date' => now()->subDay(), 'allow_late_submission' => false]);
        [$token] = $this->makeStudent();

        $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Too late.'),
            ])
            ->assertUnprocessable();
    }

    public function test_duplicate_submission_is_rejected(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake();
        $assignment = $this->makeAssignment();
        [$token] = $this->makeStudent();

        $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'First try.'),
            ])
            ->assertCreated();

        $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Second try.'),
            ])
            ->assertUnprocessable();
    }

    public function test_ai_check_job_failure_does_not_affect_the_submission_record(): void
    {
        Storage::fake('minio');
        // Anthropic responds with a server error every time — AssignmentAiCheckJob
        // catches this internally and writes status=failed; it never rethrows,
        // so the submit endpoint still succeeds.
        Http::fake(['api.anthropic.com/*' => Http::response('Internal Server Error', 500)]);
        $assignment = $this->makeAssignment();
        [$token] = $this->makeStudent();

        $response = $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Content.'),
            ])
            ->assertCreated();

        $submissionId = $response->json('data.id');
        $this->assertDatabaseHas('lms_submissions', ['id' => $submissionId]);
        $this->assertDatabaseHas('lms_submission_ai_checks', ['submission_id' => $submissionId, 'status' => 'failed']);
    }

    public function test_ai_check_succeeds_and_stores_the_score(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake(score: 7, likelyAi: false);
        $assignment = $this->makeAssignment();
        [$token] = $this->makeStudent();

        $response = $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Original content.'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('lms_submission_ai_checks', [
            'submission_id' => $response->json('data.id'),
            'status' => 'completed',
            'ai_score' => 7,
            'likely_ai_generated' => false,
        ]);
    }

    public function test_ai_check_fails_cleanly_when_no_api_key_is_configured(): void
    {
        $this->school->update(['lms_ai_api_key' => null]);
        Storage::fake('minio');
        $assignment = $this->makeAssignment();
        [$token] = $this->makeStudent();

        $response = $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Content.'),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('lms_submission_ai_checks', [
            'submission_id' => $response->json('data.id'),
            'status' => 'failed',
        ]);
    }

    public function test_student_cannot_view_another_students_submission(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake();
        $assignment = $this->makeAssignment();
        [$tokenA] = $this->makeStudent();
        [$tokenB] = $this->makeStudent();

        $submissionId = $this->withToken($tokenA)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Student A work.'),
            ])->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($tokenB)
            ->getJson("/api/v2/lms/submissions/{$submissionId}")
            ->assertForbidden();
    }

    public function test_teacher_can_grade_a_submission(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake();
        $assignment = $this->makeAssignment();
        [$token] = $this->makeStudent();

        $submissionId = $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Grade me.'),
            ])->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/submissions/{$submissionId}/grade", [
                'marks_awarded' => 85,
                'teacher_feedback' => 'Good work.',
            ])
            ->assertOk()
            ->assertJsonFragment(['marks_awarded' => 85, 'teacher_feedback' => 'Good work.']);
    }

    public function test_grade_cannot_exceed_max_marks(): void
    {
        Storage::fake('minio');
        $this->anthropicSuccessFake();
        $assignment = $this->makeAssignment(['max_marks' => 50]);
        [$token] = $this->makeStudent();

        $submissionId = $this->withToken($token)
            ->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [
                'file' => UploadedFile::fake()->createWithContent('essay.txt', 'Grade me.'),
            ])->json('data.id');

        $this->app['auth']->forgetGuards();

        $this->withToken($this->teacherToken())
            ->postJson("/api/v2/lms/submissions/{$submissionId}/grade", ['marks_awarded' => 999])
            ->assertUnprocessable();
    }

    public function test_requires_auth(): void
    {
        $assignment = $this->makeAssignment();

        $this->postJson("/api/v2/lms/assignments/{$assignment->id}/submit", [])
            ->assertUnauthorized();
    }
}
