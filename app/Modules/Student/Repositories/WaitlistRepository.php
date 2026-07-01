<?php

namespace App\Modules\Student\Repositories;

use App\Modules\Student\Models\StudentWaitlist;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class WaitlistRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StudentWaitlist::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'waitlist';
    }

    /**
     * All waiting entries for a class/section, ordered by position.
     *
     * @return Collection<int, StudentWaitlist>
     */
    public function getWaiting(int $schoolId, int $classId, ?int $sectionId, int $yearId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:class:{$classId}:section:{$sectionId}:year:{$yearId}:waiting"),
            fn () => StudentWaitlist::where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where('academic_year_id', $yearId)
                ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
                ->waiting()
                ->get(),
        );
    }

    /**
     * Next position number for a given class/section/year.
     */
    public function nextPosition(int $schoolId, int $classId, ?int $sectionId, int $yearId): int
    {
        $max = StudentWaitlist::where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('academic_year_id', $yearId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->max('position');

        return ($max ?? 0) + 1;
    }
}
