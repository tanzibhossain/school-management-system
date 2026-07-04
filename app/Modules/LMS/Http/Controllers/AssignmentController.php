<?php

namespace App\Modules\LMS\Http\Controllers;

use App\Modules\LMS\Http\Requests\StoreAssignmentRequest;
use App\Modules\LMS\Http\Requests\UpdateAssignmentRequest;
use App\Modules\LMS\Http\Resources\AssignmentResource;
use App\Modules\LMS\Http\Resources\SubmissionResource;
use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Services\AssignmentService;
use App\Modules\LMS\Services\SubmissionService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $service,
        private readonly SubmissionService $submissionService,
    ) {}

    /** GET /v2/lms/courses/{courseId}/assignments — open to admin/teacher/student (all need to see what's due). */
    public function index(int $courseId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        Course::forSchool($schoolId)->findOrFail($courseId);

        return AssignmentResource::collection($this->service->forCourse($schoolId, $courseId));
    }

    public function store(StoreAssignmentRequest $request, int $courseId): AssignmentResource
    {
        $course = Course::forSchool(app('current_school_id'))->findOrFail($courseId);
        $this->assertCanManage($request, $course);

        return new AssignmentResource($this->service->create($course, $request->validated()));
    }

    public function update(UpdateAssignmentRequest $request, int $id): AssignmentResource
    {
        $assignment = Assignment::forSchool(app('current_school_id'))->with('course')->findOrFail($id);
        $this->assertCanManage($request, $assignment->course);

        return new AssignmentResource($this->service->update($assignment, $request->validated()));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $assignment = Assignment::forSchool(app('current_school_id'))->with('course')->findOrFail($id);
        $this->assertCanManage($request, $assignment->course);
        $this->service->delete($assignment);

        return response()->json(['message' => 'Assignment deleted.']);
    }

    /** GET /v2/lms/assignments/{id}/submissions — teacher: all submissions + AI check results. */
    public function submissions(Request $request, int $id): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $assignment = Assignment::forSchool($schoolId)->with('course')->findOrFail($id);
        $this->assertCanManage($request, $assignment->course);

        return SubmissionResource::collection($this->submissionService->forAssignment($schoolId, $id));
    }

    private function assertCanManage(Request $request, Course $course): void
    {
        if ($request->user()->tokenCan('admin:*')) {
            return;
        }

        $staff = Staff::where('school_id', app('current_school_id'))->where('user_id', $request->user()->id)->first();

        if (! $staff || $course->teacher_id !== $staff->id) {
            throw new AccessDeniedHttpException('You do not own this course.');
        }
    }
}
