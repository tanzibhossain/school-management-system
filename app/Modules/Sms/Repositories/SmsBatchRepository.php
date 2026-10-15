<?php

namespace App\Modules\Sms\Repositories;

use App\Modules\Sms\Models\SmsBatch;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * Batch status moves queued -> processing -> completed/failed while a client
 * polls it, so listing/status reads here are UNCACHED (same caution as
 * IdCard/Loan/Leave's request repositories) — findOrFail (inherited from
 * BaseRepository) is still cache-aside but gets flushed by SmsBatchObserver
 * on every save.
 */
class SmsBatchRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(SmsBatch::class, $cache);
    }

    public function forSchool(int $schoolId): Collection
    {
        return SmsBatch::forSchool($schoolId)
            ->with('logs')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Students with an unpaid/partial invoice, aggregated to one row each with
     * their total remaining due — built directly against Payment's tables
     * rather than depending on the Report module (each module stands alone).
     * Uncached: dues must be current at the moment reminders go out.
     *
     * @param  array{class_id?: int|null, section_id?: int|null, academic_year_id?: int|null, target_ids?: array<int>|null}  $filters
     * @return SupportCollection<int, object{student_id: int, total_due: float, currency: string}&\stdClass>
     */
    public function studentsWithDues(int $schoolId, array $filters): SupportCollection
    {
        $query = DB::table('invoices')
            ->join('students', 'students.id', '=', 'invoices.student_id')
            ->leftJoin('student_academics', function ($join): void {
                $join->on('student_academics.student_id', '=', 'invoices.student_id')
                    ->on('student_academics.academic_year_id', '=', 'invoices.academic_year_id');
            })
            ->where('invoices.school_id', $schoolId)
            ->whereIn('invoices.status', ['unpaid', 'partial'])
            ->where('students.is_trash', false)
            ->where('students.status', 'active');

        if (! empty($filters['class_id'])) {
            $query->where('student_academics.class_id', $filters['class_id']);
        }
        if (! empty($filters['section_id'])) {
            $query->where('student_academics.section_id', $filters['section_id']);
        }
        if (! empty($filters['academic_year_id'])) {
            $query->where('invoices.academic_year_id', $filters['academic_year_id']);
        }
        if (! empty($filters['target_ids'])) {
            $query->whereIn('invoices.student_id', $filters['target_ids']);
        }

        $rows = $query->select([
            'invoices.student_id', 'invoices.amount_due', 'invoices.amount_paid',
            'invoices.credit_applied', 'invoices.currency',
        ])->get();

        return $rows->groupBy('student_id')->map(function (SupportCollection $group) {
            $first = $group->first();
            $remaining = $group->sum(fn ($row) => (float) $row->amount_due - (float) $row->amount_paid - (float) $row->credit_applied);

            return (object) [
                'student_id' => (int) $first->student_id,
                'total_due' => round($remaining, 2),
                'currency' => (string) $first->currency,
            ];
        })->values();
    }
}
