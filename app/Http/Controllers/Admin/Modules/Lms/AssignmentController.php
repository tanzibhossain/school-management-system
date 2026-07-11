<?php

namespace App\Http\Controllers\Admin\Modules\Lms;

use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Services\AssignmentService;
use App\Modules\LMS\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly SubmissionService $submissions,
    ) {}

    public function store(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::where('school_id', app('current_school_id'))->findOrFail($courseId);

        $data = $request->validate([
            'title'                 => ['required', 'string', 'max:150'],
            'instructions'          => ['nullable', 'string'],
            'due_date'              => ['required', 'date'],
            'max_marks'             => ['required', 'integer', 'min:1', 'max:1000'],
            'allow_late_submission' => ['nullable', 'boolean'],
        ]);
        $data['allow_late_submission'] = $request->boolean('allow_late_submission');

        $this->assignments->create($course, $data);

        return back()->with('status', 'Assignment created.');
    }

    public function destroy(int $courseId, int $assignmentId): RedirectResponse
    {
        $assignment = $this->find($courseId, $assignmentId);
        $this->assignments->delete($assignment);

        return redirect()->route('admin.lms.courses.show', $courseId)->with('status', 'Assignment removed.');
    }

    public function show(int $courseId, int $assignmentId): View
    {
        $schoolId = app('current_school_id');
        $assignment = $this->find($courseId, $assignmentId);
        $assignment->load('course:id,title');

        $submissions = $this->submissions->forAssignment($schoolId, $assignment->id);

        return view('admin.modules.lms.assignments.show', compact('assignment', 'submissions'));
    }

    private function find(int $courseId, int $assignmentId): Assignment
    {
        return Assignment::where('school_id', app('current_school_id'))
            ->where('course_id', $courseId)->findOrFail($assignmentId);
    }
}
