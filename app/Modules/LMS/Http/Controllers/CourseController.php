<?php

namespace App\Modules\LMS\Http\Controllers;

use App\Modules\LMS\Http\Requests\StoreCourseRequest;
use App\Modules\LMS\Http\Requests\UpdateCourseRequest;
use App\Modules\LMS\Http\Resources\CourseResource;
use App\Modules\LMS\Http\Resources\LessonResource;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Repositories\CourseRepository;
use App\Modules\LMS\Services\CourseService;
use App\Modules\LMS\Services\LessonService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CourseController extends Controller
{
    public function __construct(
        private readonly CourseService $service,
        private readonly LessonService $lessonService,
        private readonly CourseRepository $repository,
    ) {}

    /**
     * GET /v2/lms/courses — a student sees courses for their own current
     * class; a teacher/admin must pass ?class_id= to browse a class's
     * courses (a teacher's OWN courses across every class are available via
     * their own token without a class_id, since that's the common case for
     * "what am I teaching").
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');

        if ($this->isStudentToken($request)) {
            $student = Student::where('school_id', $schoolId)->where('user_id', $request->user()->id)->firstOrFail();

            return CourseResource::collection($this->service->forStudent($schoolId, $student));
        }

        if ($request->filled('class_id')) {
            return CourseResource::collection($this->repository->forClass($schoolId, (int) $request->query('class_id')));
        }

        $staff = Staff::where('school_id', $schoolId)->where('user_id', $request->user()->id)->first();

        if ($staff) {
            return CourseResource::collection($this->service->forTeacher($schoolId, $staff->id));
        }

        return CourseResource::collection($this->repository->all($schoolId));
    }

    public function store(StoreCourseRequest $request): CourseResource
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();
        $data['teacher_id'] = $this->resolveTeacherId($request, $data['teacher_id'] ?? null);

        return new CourseResource($this->service->create($schoolId, $data));
    }

    public function update(UpdateCourseRequest $request, int $id): CourseResource
    {
        $course = Course::forSchool(app('current_school_id'))->findOrFail($id);
        $this->assertCanManage($request, $course);
        $data = $request->validated();

        if (array_key_exists('teacher_id', $data)) {
            $data['teacher_id'] = $this->resolveTeacherId($request, $data['teacher_id']);
        }

        return new CourseResource($this->service->update($course, $data));
    }

    public function destroy(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $course = Course::forSchool(app('current_school_id'))->findOrFail($id);
        $this->assertCanManage($request, $course);
        $this->service->delete($course);

        return response()->json(['message' => 'Course deleted.']);
    }

    /** GET /v2/lms/courses/{id}/lessons — published-only for students. */
    public function lessons(Request $request, int $id): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        Course::forSchool($schoolId)->findOrFail($id);

        $publishedOnly = $this->isStudentToken($request);

        return LessonResource::collection($this->lessonService->forCourse($schoolId, $id, $publishedOnly));
    }

    /** A non-admin teacher may only ever create/edit courses under their own name. */
    private function resolveTeacherId(Request $request, ?int $requested): ?int
    {
        if ($request->user()->tokenCan('admin:*')) {
            return $requested;
        }

        $staff = Staff::where('school_id', app('current_school_id'))->where('user_id', $request->user()->id)->first();

        if (! $staff) {
            throw ValidationException::withMessages(['teacher_id' => 'No staff record found for this user.']);
        }

        return $staff->id;
    }

    /**
     * An admin token carries the '*' ability, which Sanctum's tokenCan()
     * treats as matching every ability string — including 'student:*'. Admin
     * must never be misidentified as "the student" branch, so it's excluded
     * explicitly rather than relying on tokenCan('student:*') alone.
     */
    private function isStudentToken(Request $request): bool
    {
        return ! $request->user()->tokenCan('admin:*') && $request->user()->tokenCan('student:*');
    }

    /** A non-admin teacher may only manage a course they own. */
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
