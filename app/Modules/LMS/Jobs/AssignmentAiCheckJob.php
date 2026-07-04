<?php

namespace App\Modules\LMS\Jobs;

use App\Modules\LMS\Gateways\AiCheckerContract;
use App\Modules\LMS\Models\Submission;
use App\Modules\LMS\Models\SubmissionAiCheck;
use App\Modules\LMS\Services\SubmissionContentExtractor;
use App\Modules\School\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Dispatched right after a submission is created (see SubmissionService).
 * A submission's own success never depends on this job — it always runs
 * after the submission row is already committed.
 *
 * $tries/backoff() are declared for a real queue worker (Horizon, processing
 * off Redis) to use if it ever requeues this job. In THIS codebase's actual
 * runtime behavior under QUEUE_CONNECTION=sync (every test, and any
 * deployment without Horizon running), an exception thrown out of handle()
 * does NOT get swallowed by the queue and does NOT trigger a requeue — it
 * propagates straight back into the HTTP request that dispatched it. That
 * was confirmed the hard way: an earlier version of this job let the
 * Anthropic API call's exception bubble up "for the queue to retry" and it
 * surfaced as a 500 on the submit endpoint instead. Fixed by catching
 * everything here and never rethrowing — the exact same "swallow, don't
 * rethrow" pattern Sms/IdCard/DataImport's batch jobs already use for
 * identical reasons.
 */
class AssignmentAiCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $submissionId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SubmissionContentExtractor $extractor, AiCheckerContract $checker): void
    {
        $submission = Submission::findOrFail($this->submissionId);
        $school = School::findOrFail($submission->school_id);

        $check = SubmissionAiCheck::firstOrCreate(
            ['submission_id' => $submission->id],
            ['school_id' => $submission->school_id, 'status' => 'pending'],
        );

        if (empty($school->lms_ai_api_key)) {
            $check->update([
                'status' => 'failed',
                'error_message' => 'AI checking is not configured for this school (no API key set in School Settings).',
                'checked_at' => now(),
            ]);

            return;
        }

        $localPath = null;

        try {
            $localPath = $this->downloadToTemp($submission->file_path);
            $extension = pathinfo($submission->file_path, PATHINFO_EXTENSION);
            $content = $extractor->extract($localPath, $extension);

            $result = $checker->check($school->lms_ai_api_key, $content);

            $check->update([
                'status' => 'completed',
                'ai_score' => $result->aiScore,
                'likely_ai_generated' => $result->likelyAiGenerated,
                'originality_note' => $result->originalityNote,
                'raw_response' => $result->rawResponse,
                'checked_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Covers both extraction failures (bad format, corrupt file) and
            // the AI API call itself (network error, non-2xx, unparseable
            // response) — neither may ever escape this job.
            $check->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'checked_at' => now(),
            ]);
        } finally {
            if ($localPath && file_exists($localPath)) {
                unlink($localPath);
            }
        }
    }

    private function downloadToTemp(string $storedPath): string
    {
        $contents = Storage::disk('minio')->get($storedPath);
        $extension = pathinfo($storedPath, PATHINFO_EXTENSION);
        $tempPath = tempnam(sys_get_temp_dir(), 'lms_ai_') . '.' . $extension;

        file_put_contents($tempPath, $contents);

        return $tempPath;
    }
}
