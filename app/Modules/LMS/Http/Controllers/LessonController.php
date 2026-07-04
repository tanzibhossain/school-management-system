<?php

namespace App\Modules\LMS\Http\Controllers;

use App\Modules\LMS\Http\Requests\StoreLessonRequest;
use App\Modules\LMS\Http\Requests\UpdateLessonRequest;
use App\Modules\LMS\Http\Resources\LessonResource;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Modules\LMS\Services\LessonService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LessonController extends Controller
{
    public function __construct(
        private readonly LessonService $service,
    ) {}

    public function store(StoreLessonRequest $request, int $courseId): LessonResource
    {
        $course = Course::forSchool(app('current_school_id'))->findOrFail($courseId);
        $this->assertCanManage($request, $course);

        return new LessonResource($this->service->create($course, $request->safe()->except('file'), $request->file('file')));
    }

    public function update(UpdateLessonRequest $request, int $id): LessonResource
    {
        $lesson = Lesson::forSchool(app('current_school_id'))->with('course')->findOrFail($id);
        $this->assertCanManage($request, $lesson->course);

        return new LessonResource($this->service->update($lesson, $request->safe()->except('file'), $request->file('file')));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $lesson = Lesson::forSchool(app('current_school_id'))->with('course')->findOrFail($id);
        $this->assertCanManage($request, $lesson->course);
        $this->service->delete($lesson);

        return response()->json(['message' => 'Lesson deleted.']);
    }

    /** GET /v2/lms/lessons/{id} — students may only fetch a published lesson. */
    public function show(Request $request, int $id): LessonResource
    {
        $query = Lesson::forSchool(app('current_school_id'));

        // Exclude admin explicitly — Sanctum's tokenCan() treats an admin
        // token's '*' ability as matching 'student:*' too.
        if (! $request->user()->tokenCan('admin:*') && $request->user()->tokenCan('student:*')) {
            $query->published();
        }

        return new LessonResource($query->findOrFail($id));
    }

    /** POST /v2/lms/lessons/{id}/publish */
    public function publish(Request $request, int $id): LessonResource
    {
        $lesson = Lesson::forSchool(app('current_school_id'))->with('course')->findOrFail($id);
        $this->assertCanManage($request, $lesson->course);

        return new LessonResource($this->service->publishLesson($lesson));
    }

    /** A non-admin teacher may only manage lessons under a course they own. */
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
