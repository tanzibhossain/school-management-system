<?php

namespace App\Modules\Payment\Repositories;

use App\Modules\Payment\Models\Payment;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Payment::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'payment';
    }

    /**
     * Paginated payment history with filters.
     * Financial writes never use cache — reads only.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Payment::with('invoice')
            ->where('school_id', $schoolId)
            ->where('is_reversed', false);

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['method'])) {
            $query->where('method', $filters['method']);
        }

        if (isset($filters['cheque_status'])) {
            $query->where('cheque_status', $filters['cheque_status']);
        }

        return $query->orderByDesc('paid_at')->paginate($perPage);
    }

    /**
     * All pending (submitted) cheques for a school — for the cheque management screen.
     */
    public function pendingCheques(int $schoolId, int $perPage = 30): LengthAwarePaginator
    {
        return Payment::with('invoice')
            ->where('school_id', $schoolId)
            ->pendingCheques()
            ->orderBy('cheque_date')
            ->paginate($perPage);
    }
}
