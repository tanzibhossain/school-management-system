<?php

namespace App\Modules\LMS\Services;

use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Repositories\CourseRepository;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Database\Eloquent\Collection;

class CourseService
{
    public function __construct(
        private readonly CourseRepository $repository,
    ) {}

    public function create(int $schoolId, array $data): Course
    {
        $data['school_id'] = $schoolId;

        return $this->repository->create($data);
    }

    public function update(Course $course, array $data): Course
    {
        return $this->repository->update($course, $data);
    }

    public function delete(Course $course): bool
    {
        return $this->repository->delete($course);
    }

    /** @return Collection<int, Course> */
    public function forTeacher(int $schoolId, int $teacherId): Collection
    {
        return $this->repository->forTeacher($schoolId, $teacherId);
    }

    /**
     * Courses visible to a student: active courses for the class the student
     * is currently enrolled in. Not filtered further by StudentSubject
     * enrollment — a student sees every course for their class, same breadth
     * as Attendance/Mark's class-wide reads (a course being open to a whole
     * class, not just students who picked it as optional, matches how course
     * platforms in general behave — narrowing by is_optional would need a
     * subject_id -> subject_relation_id join this table deliberately doesn't
     * have, per the DevPlan's literal subject_id column).
     *
     * @return Collection<int, Course>
     */
    public function forStudent(int $schoolId, Student $student): Collection
    {
        /** @var StudentAcademic|null $academic */
        $academic = $student->currentAcademic;

        if (! $academic) {
            return new Collection();
        }

        return $this->repository->forClass($schoolId, $academic->class_id);
    }
}
