<?php

namespace App\Modules\LMS\Http\Controllers;

use App\Modules\LMS\Http\Requests\GradeSubmissionRequest;
use App\Modules\LMS\Http\Requests\SubmitAssignmentRequest;
use App\Modules\LMS\Http\Resources\SubmissionResource;
use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Submission;
use App\Modules\LMS\Services\SubmissionService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SubmissionController extends Controller
{
    public function __construct(
        private readonly SubmissionService $service,
    ) {}

    /** POST /v2/lms/assignments/{id}/submit */
    public function submit(SubmitAssignmentRequest $request, int $assignmentId): JsonResponse
    {
        $schoolId = app('current_school_id');
        $assignment = Assignment::forSchool($schoolId)->findOrFail($assignmentId);
        $student = Student::where('school_id', $schoolId)->where('user_id', $request->user()->id)->firstOrFail();

        $submission = $this->service->submitAssignment($assignment, $student, $request->file('file'));

        // submitAssignment() returns a fresh()-refetched instance, so
        // wasRecentlyCreated is false and the auto-201 behavior doesn't kick
        // in — set it explicitly (same gotcha as Sms's requestManual()).
        return (new SubmissionResource($submission))->response()->setStatusCode(201);
    }

    /** POST /v2/lms/submissions/{id}/grade */
    public function grade(GradeSubmissionRequest $request, int $id): SubmissionResource
    {
        $schoolId = app('current_school_id');
        $submission = Submission::forSchool($schoolId)->with('assignment.course')->findOrFail($id);
        $this->assertCanGrade($request, $submission);

        $data = $request->validated();

        return new SubmissionResource($this->service->gradeSubmission($submission, (int) $data['marks_awarded'], $data['teacher_feedback'] ?? null));
    }

    /** GET /v2/lms/submissions/{id} — student may only view their own. */
    public function show(Request $request, int $id): SubmissionResource
    {
        $schoolId = app('current_school_id');
        $submission = Submission::forSchool($schoolId)->with(['student', 'aiCheck'])->findOrFail($id);

        // Exclude admin explicitly — an admin token's '*' ability otherwise
        // also satisfies tokenCan('student:*'), which would wrongly subject
        // an admin to the "own submission only" check below.
        if (! $request->user()->tokenCan('admin:*') && $request->user()->tokenCan('student:*')) {
            $student = Student::where('school_id', $schoolId)->where('user_id', $request->user()->id)->first();

            if (! $student || $submission->student_id !== $student->id) {
                throw new AccessDeniedHttpException('You may not view another student\'s submission.');
            }
        }

        return new SubmissionResource($submission);
    }

    /** GET /v2/lms/student/me/submissions */
    public function myOwn(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->where('user_id', $request->user()->id)->firstOrFail();

        return SubmissionResource::collection($this->service->forStudent($schoolId, $student->id));
    }

    private function assertCanGrade(Request $request, Submission $submission): void
    {
        if ($request->user()->tokenCan('admin:*')) {
            return;
        }

        $staff = Staff::where('school_id', app('current_school_id'))->where('user_id', $request->user()->id)->first();

        if (! $staff || $submission->assignment->course->teacher_id !== $staff->id) {
            throw new AccessDeniedHttpException('You do not own this course.');
        }
    }
}
