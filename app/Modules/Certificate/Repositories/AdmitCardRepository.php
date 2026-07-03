<?php

namespace App\Modules\Certificate\Repositories;

use App\Modules\Certificate\Models\AdmitCard;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class AdmitCardRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(AdmitCard::class, $cache);
    }

    public function forStudent(int $schoolId, int $studentId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:student:{$studentId}"),
            fn () => AdmitCard::forSchool($schoolId)
                ->where('student_id', $studentId)
                ->with('exam:id,title,start_date,end_date')
                ->orderByDesc('generated_at')
                ->get(),
        );
    }

    public function findForStudentAndExam(int $schoolId, int $studentId, int $examId): ?AdmitCard
    {
        return AdmitCard::forSchool($schoolId)
            ->where('student_id', $studentId)
            ->where('exam_id', $examId)
            ->first();
    }
}
