<?php

namespace App\Modules\Payment\Repositories;

use App\Modules\Payment\Models\Invoice;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class InvoiceRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Invoice::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'invoice';
    }

    /**
     * Paginated invoice list for admin with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Invoice::with('items')
            ->where('school_id', $schoolId);

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * All unpaid invoices for a student (for portal display).
     *
     * @return Collection<int, Invoice>
     */
    public function unpaidForStudent(int $schoolId, int $studentId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:student:{$studentId}:unpaid"),
            fn () => Invoice::with('items')
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('due_date')
                ->get(),
        );
    }
}
