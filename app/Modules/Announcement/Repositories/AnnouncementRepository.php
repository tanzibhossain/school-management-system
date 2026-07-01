<?php

namespace App\Modules\Announcement\Repositories;

use App\Modules\Announcement\Models\Announcement;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AnnouncementRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Announcement::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'announcements';
    }

    /**
     * Admin list — all non-trashed, including drafts/scheduled/expired.
     *
     * @param array<string, mixed> $filters
     */
    public function paginateForAdmin(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Announcement::where('school_id', $schoolId)
            ->notTrashed()
            ->with(['attachments']);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['audience'])) {
            $query->where('audience', $filters['audience']);
        }
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }
        if (isset($filters['include_expired']) && $filters['include_expired']) {
            // no extra filter — expired are already included
        }

        return $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Visible announcements for portal users — published, not expired, audience-scoped.
     *
     * @param  string[]  $audiences  e.g. ['all', 'teachers']
     * @return Collection<int, Announcement>
     */
    public function listVisible(int $schoolId, array $audiences): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:visible:" . implode(',', $audiences)),
            fn () => Announcement::where('school_id', $schoolId)
                ->visible()
                ->whereIn('audience', $audiences)
                ->with(['attachments', 'targets'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('publish_at')
                ->get(),
        );
    }
}
