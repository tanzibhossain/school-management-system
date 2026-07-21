<?php

namespace App\Http\Controllers\Admin\Modules\Lms;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Subject;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Services\CourseService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private readonly CourseService $courses) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.modules.lms.courses.index', [
            'courses' => Course::where('school_id', $schoolId)
                ->with(['schoolClass:id,name', 'subject:id,name', 'teacher:id,name'])
                ->withCount(['lessons', 'assignments'])
                ->orderBy('title')->get(),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'teachers' => Staff::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->courses->create(app('current_school_id'), $this->validated($request));

        return back()->with('status', __('Course Created.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $course = Course::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->courses->update($course, $this->validated($request));

        return back()->with('status', __('Course Updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $course = Course::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->courses->delete($course);

        return redirect()->route('admin.lms.courses.index')->with('status', __('Course Deleted.'));
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $course = Course::where('school_id', $schoolId)
            ->with(['schoolClass:id,name', 'subject:id,name', 'teacher:id,name',
                'lessons' => fn ($q) => $q->orderBy('sort_order'),
                'assignments' => fn ($q) => $q->orderByDesc('id')])
            ->withCount(['assignments'])
            ->findOrFail($id);

        return view('admin.modules.lms.courses.show', compact('course'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'subject_id' => ['required', 'integer', "exists:subjects,id,school_id,{$schoolId}"],
            'teacher_id' => ['nullable', 'integer', "exists:staff,id,school_id,{$schoolId}"],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [], ['class_id' => 'class', 'subject_id' => 'subject', 'teacher_id' => 'teacher']);
        $data['is_active'] = true;

        return $data;
    }
}
