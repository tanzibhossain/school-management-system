<?php

namespace App\Modules\Transport\Repositories;

use App\Modules\Transport\Models\StudentTransportAssignment;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

class StudentTransportAssignmentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(StudentTransportAssignment::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'student-transport-assignment';
    }

    /**
     * @param  array{route_id?: int|null, status?: string|null}  $filters
     * @return Collection<int, StudentTransportAssignment>
     */
    public function filtered(int $schoolId, array $filters): Collection
    {
        $routeId = $filters['route_id'] ?? null;
        $status = $filters['status'] ?? null;

        return $this->remember(
            $this->cacheKey("school:{$schoolId}:route:".($routeId ?? 'all').':status:'.($status ?? 'all')),
            fn () => StudentTransportAssignment::forSchool($schoolId)
                ->when($routeId, fn ($q) => $q->where('transport_route_id', $routeId))
                ->when($status, fn ($q) => $q->where('status', $status))
                ->with(['route', 'student'])
                ->latest('id')
                ->get(),
        );
    }
}
