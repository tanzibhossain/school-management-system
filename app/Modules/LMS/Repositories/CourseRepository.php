<?php

namespace App\Modules\LMS\Repositories;

use App\Modules\LMS\Models\Course;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Course::class, $cache);
    }

    /** @return Collection<int, Course> */
    public function forClass(int $schoolId, int $classId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:class:{$classId}:active"),
            fn () => Course::forSchool($schoolId)->active()->where('class_id', $classId)->get(),
        );
    }

    /** @return Collection<int, Course> */
    public function forTeacher(int $schoolId, int $teacherId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:teacher:{$teacherId}"),
            fn () => Course::forSchool($schoolId)->where('teacher_id', $teacherId)->get(),
        );
    }
}
