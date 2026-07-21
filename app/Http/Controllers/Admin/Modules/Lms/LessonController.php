<?php

namespace App\Http\Controllers\Admin\Modules\Lms;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Modules\LMS\Services\LessonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LessonController extends Controller
{
    public function __construct(private readonly LessonService $lessons) {}

    public function store(Request $request, int $courseId): RedirectResponse
    {
        $course = Course::where('school_id', app('current_school_id'))->findOrFail($courseId);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content_type' => ['required', 'in:text,video'],
            'body_text' => ['nullable', 'required_if:content_type,text', 'string'],
            'video_url' => ['nullable', 'required_if:content_type,video', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
        ], [], ['content_type' => 'type', 'body_text' => 'content', 'video_url' => 'video URL']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');

        $this->lessons->create($course, $data);

        return back()->with('status', 'Lesson added.');
    }

    public function publish(int $courseId, int $lessonId): RedirectResponse
    {
        $lesson = $this->find($courseId, $lessonId);
        $this->lessons->publishLesson($lesson);

        return back()->with('status', 'Lesson published.');
    }

    public function destroy(int $courseId, int $lessonId): RedirectResponse
    {
        $this->lessons->delete($this->find($courseId, $lessonId));

        return back()->with('status', 'Lesson removed.');
    }

    private function find(int $courseId, int $lessonId): Lesson
    {
        return Lesson::where('school_id', app('current_school_id'))
            ->where('course_id', $courseId)->findOrFail($lessonId);
    }
}
