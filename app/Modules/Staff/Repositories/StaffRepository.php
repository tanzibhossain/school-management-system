<?php

namespace App\Modules\Staff\Repositories;

use App\Modules\Staff\Models\Staff;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Staff::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'staff';
    }

    /**
     * Paginated list — filterable by status, designation, department, search.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Staff::with(['designation', 'department'])
            ->where('school_id', $schoolId)
            ->where('is_trash', false);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['designation_id'])) {
            $query->where('designation_id', $filters['designation_id']);
        }
        if (! empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (! empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('employee_id', 'like', "%{$search}%")
                ->orWhere('rfid_number', 'like', "%{$search}%")
            );
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Active staff assigned to a class/section for a given year.
     *
     * @return Collection<int, Staff>
     */
    public function activeByClassSection(int $schoolId, int $classId, int $sectionId, int $yearId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:year:{$yearId}:class:{$classId}:section:{$sectionId}:active"),
            fn () => Staff::with(['designation', 'academics'])
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->where('is_trash', false)
                ->whereHas('academics', fn ($q) => $q
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('academic_year_id', $yearId)
                )
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Find staff by RFID number (for attendance punch-in).
     */
    public function findByRfid(string $rfidNumber, int $schoolId): ?Staff
    {
        return Staff::where('school_id', $schoolId)
            ->where('rfid_number', $rfidNumber)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Find staff linked to a user account.
     */
    public function findByUserId(int $userId, int $schoolId): ?Staff
    {
        return Staff::where('school_id', $schoolId)
            ->where('user_id', $userId)
            ->first();
    }
}
