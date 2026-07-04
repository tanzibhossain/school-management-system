<?php

namespace App\Modules\LMS\Services;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class LessonService
{
    public function create(Course $course, array $data, ?UploadedFile $file = null): Lesson
    {
        if ($file) {
            $data['file_path'] = $this->storeFile($course, $file);
        }

        $data['school_id'] = $course->school_id;
        $data['course_id'] = $course->id;

        return Lesson::create($data);
    }

    public function update(Lesson $lesson, array $data, ?UploadedFile $file = null): Lesson
    {
        if ($file) {
            $data['file_path'] = $this->storeFile($lesson->course, $file);
        }

        $lesson->update($data);

        return $lesson->fresh();
    }

    public function delete(Lesson $lesson): bool
    {
        return (bool) $lesson->delete();
    }

    /** Students can now see it. */
    public function publishLesson(Lesson $lesson): Lesson
    {
        $lesson->update(['is_published' => true]);

        return $lesson->fresh();
    }

    /** @return Collection<int, Lesson> */
    public function forCourse(int $schoolId, int $courseId, bool $publishedOnly = false): Collection
    {
        $query = Lesson::forSchool($schoolId)->where('course_id', $courseId)->orderBy('sort_order');

        if ($publishedOnly) {
            $query->published();
        }

        return $query->get();
    }

    private function storeFile(Course $course, UploadedFile $file): string
    {
        $path = "{$course->school_id}/lms/lessons/{$course->id}";
        $filename = uniqid('lesson_') . '.' . $file->getClientOriginalExtension();

        Storage::disk('minio')->putFileAs($path, $file, $filename);

        return "{$path}/{$filename}";
    }
}
