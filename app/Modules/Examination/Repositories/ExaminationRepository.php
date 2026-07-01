<?php

namespace App\Modules\Examination\Repositories;

use App\Modules\Examination\Models\Exam;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ExaminationRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Exam::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'exam';
    }

    /**
     * Paginated list with optional filters — not cached (admin list view).
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Exam::with('examType')
            ->withCount('subjects')
            ->where('school_id', $schoolId);

        if (isset($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['exam_type_id'])) {
            $query->where('exam_type_id', $filters['exam_type_id']);
        }

        return $query->orderByDesc('start_date')->paginate($perPage);
    }

    /**
     * All published exams for a class+year (cached — used by student portal & Mark module).
     *
     * @return Collection<int, Exam>
     */
    public function publishedForClass(int $schoolId, int $classId, int $yearId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:class:{$classId}:year:{$yearId}:published"),
            fn () => Exam::with(['examType', 'subjects.subjectRelation.subject'])
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where('academic_year_id', $yearId)
                ->where('status', 'published')
                ->orderBy('start_date')
                ->get(),
        );
    }
}
