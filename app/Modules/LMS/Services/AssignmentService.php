<?php

namespace App\Modules\LMS\Services;

use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Course;
use Illuminate\Database\Eloquent\Collection;

class AssignmentService
{
    public function create(Course $course, array $data): Assignment
    {
        $data['school_id'] = $course->school_id;
        $data['course_id'] = $course->id;

        return Assignment::create($data);
    }

    public function update(Assignment $assignment, array $data): Assignment
    {
        $assignment->update($data);

        return $assignment->fresh();
    }

    public function delete(Assignment $assignment): bool
    {
        return (bool) $assignment->delete();
    }

    /** @return Collection<int, Assignment> */
    public function forCourse(int $schoolId, int $courseId): Collection
    {
        return Assignment::forSchool($schoolId)->where('course_id', $courseId)->orderByDesc('due_date')->get();
    }
}
