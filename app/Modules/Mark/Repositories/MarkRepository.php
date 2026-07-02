<?php

namespace App\Modules\Mark\Repositories;

use App\Modules\Mark\Models\Mark;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Mark reads are intentionally UNCACHED during entry (mid-entry staleness),
 * and there is NO cache on mark writes (CLAUDE.md rule, same as Payment).
 * The only cached read in this module is the tabulation sheet
 * (Cache::tags(['tabulation']) in ResultCalculationService).
 */
class MarkRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Mark::class, $cache);
    }

    /** All marks for one division (mark-entry sheet view). */
    public function forDivision(int $schoolId, int $divisionId): Collection
    {
        return Mark::forSchool($schoolId)
            ->where('mark_division_id', $divisionId)
            ->with('student:id,name,admission_number')
            ->orderBy('student_id')
            ->get();
    }
}
