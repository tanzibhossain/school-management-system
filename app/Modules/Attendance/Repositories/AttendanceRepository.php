<?php

namespace App\Modules\Attendance\Repositories;

use App\Modules\Attendance\Models\StudentAttendance;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

/**
 * Attendance reads change constantly during the school day, so register and
 * summary queries are intentionally UNCACHED. No cache on writes either
 * (CLAUDE.md rule — high-frequency daily writes, same caution as Payment).
 */
class AttendanceRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StudentAttendance::class, $cache);
    }

    /** Day register for a class (optionally one section). */
    public function register(int $schoolId, int $classId, ?int $sectionId, string $date): Collection
    {
        return StudentAttendance::query()
            ->forSchool($schoolId)
            ->where('class_id', $classId)
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->onDate($date)
            ->with('student:id,name,admission_number')
            ->orderBy('student_id')
            ->get();
    }

    /**
     * All of one student's records in a date range.
     * whereDate (not whereBetween) — the date cast stores 'Y-m-d H:i:s' on SQLite,
     * which breaks lexicographic upper-boundary comparison against 'Y-m-d'.
     */
    public function forStudentBetween(int $schoolId, int $studentId, string $from, string $to): Collection
    {
        return StudentAttendance::query()
            ->forSchool($schoolId)
            ->where('student_id', $studentId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->orderBy('date')
            ->get();
    }

    /** Status counts per status for one student in a range. */
    public function statusCounts(int $schoolId, int $studentId, string $from, string $to): BaseCollection
    {
        return StudentAttendance::query()
            ->forSchool($schoolId)
            ->where('student_id', $studentId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }
}
