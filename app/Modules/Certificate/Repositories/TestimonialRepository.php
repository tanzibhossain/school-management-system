<?php

namespace App\Modules\Certificate\Repositories;

use App\Modules\Certificate\Models\Testimonial;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class TestimonialRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Testimonial::class, $cache);
    }

    public function forStudent(int $schoolId, int $studentId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:student:{$studentId}"),
            fn () => Testimonial::forSchool($schoolId)
                ->where('student_id', $studentId)
                ->orderByDesc('issued_date')
                ->get(),
        );
    }

    public function countForYear(int $schoolId, int $year): int
    {
        return Testimonial::forSchool($schoolId)
            ->whereYear('created_at', $year)
            ->count();
    }
}
