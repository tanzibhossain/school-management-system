<?php

namespace App\Modules\FeeItem\Repositories;

use App\Modules\FeeItem\Models\FeeItem;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FeeItemRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(FeeItem::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'fee-item';
    }

    /**
     * All active items for invoice generation:
     * matches items with null class_id (school-wide) OR the given class.
     *
     * @return Collection<int, FeeItem>
     */
    public function forInvoice(int $schoolId, int $academicYearId, int $classId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:year:{$academicYearId}:class:{$classId}:invoice"),
            fn () => FeeItem::with('category')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('is_active', true)
                ->where(function ($q) use ($classId): void {
                    $q->whereNull('class_id')->orWhere('class_id', $classId);
                })
                ->orderBy('category_id')
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Paginated admin list with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FeeItem::with('category')
            ->where('school_id', $schoolId);

        if (isset($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (isset($filters['class_id'])) {
            $query->where(function ($q) use ($filters): void {
                $q->whereNull('class_id')->orWhere('class_id', $filters['class_id']);
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('category_id')->orderBy('name')->paginate($perPage);
    }
}
