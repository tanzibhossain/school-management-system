<?php

namespace App\Modules\LMS\Services;

use App\Modules\LMS\Jobs\AssignmentAiCheckJob;
use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Submission;
use App\Modules\Student\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SubmissionService
{
    /**
     * Stores the file, creates the submission row, and dispatches the AI
     * checker job — mirrors Sms/IdCard's "dispatch after the row is
     * committed" ordering, so a job failure (or QUEUE_CONNECTION=sync running
     * it inline and it failing) can never roll back a successful submission.
     */
    public function submitAssignment(Assignment $assignment, Student $student, UploadedFile $file): Submission
    {
        if (Submission::where('assignment_id', $assignment->id)->where('student_id', $student->id)->exists()) {
            throw new UnprocessableEntityHttpException('An assignment can only be submitted once.');
        }

        $now = now();
        $isLate = $now->greaterThan($assignment->due_date);

        if ($isLate && ! $assignment->allow_late_submission) {
            throw new UnprocessableEntityHttpException('The due date has passed and late submissions are not allowed for this assignment.');
        }

        $path = "{$assignment->school_id}/lms/submissions/{$assignment->id}";
        $filename = "{$student->id}.".$file->getClientOriginalExtension();

        $submission = Submission::create([
            'school_id' => $assignment->school_id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'file_path' => "{$path}/{$filename}",
            'submitted_at' => $now,
            'late_submission' => $isLate,
        ]);

        // Upload only after the row exists — a failed insert must not leave an
        // orphaned MinIO object behind.
        Storage::disk('minio')->putFileAs($path, $file, $filename);

        AssignmentAiCheckJob::dispatch($submission->id);

        // load(), not fresh(): fresh() re-queries and discards wasRecentlyCreated,
        // which JsonResource relies on to auto-return 201 instead of 200.
        return $submission->load('aiCheck');
    }

    public function gradeSubmission(Submission $submission, int $marksAwarded, ?string $feedback): Submission
    {
        if ($marksAwarded > $submission->assignment->max_marks) {
            throw new UnprocessableEntityHttpException(
                "marks_awarded ({$marksAwarded}) cannot exceed the assignment's max_marks ({$submission->assignment->max_marks})."
            );
        }

        $submission->update([
            'marks_awarded' => $marksAwarded,
            'teacher_feedback' => $feedback,
            'graded_at' => now(),
        ]);

        return $submission->fresh(['aiCheck']);
    }

    /** Teacher view: every submission for one assignment, with its AI check. */
    public function forAssignment(int $schoolId, int $assignmentId): Collection
    {
        return Submission::forSchool($schoolId)
            ->where('assignment_id', $assignmentId)
            ->with(['student', 'aiCheck'])
            ->orderByDesc('submitted_at')
            ->get();
    }

    /** Student self-service: own submission history only. */
    public function forStudent(int $schoolId, int $studentId): Collection
    {
        return Submission::forSchool($schoolId)
            ->where('student_id', $studentId)
            ->with(['assignment', 'aiCheck'])
            ->orderByDesc('submitted_at')
            ->get();
    }
}
