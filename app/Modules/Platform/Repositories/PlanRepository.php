<?php

namespace App\Modules\Platform\Repositories;

use App\Modules\Platform\Models\Plan;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Deliberately NOT extending BaseRepository — that class's all()/find()/findOrFail()
 * assume every query is scoped `where('school_id', $schoolId)`, which doesn't apply
 * here: Plan is platform-level (no school_id at all), same reasoning Report's
 * ReportRepository already established for a different kind of cross-cutting model.
 */
class PlanRepository
{
    private const CACHE_TTL = 3600;

    public function __construct(private readonly CacheRepository $cache) {}

    /** @return Collection<int, Plan> */
    public function allActive(): Collection
    {
        return $this->cache->tags(['plan'])->remember(
            'plan:all_active',
            self::CACHE_TTL,
            fn () => Plan::query()->active()->orderBy('sort_order')->get(),
        );
    }

    /** @return Collection<int, Plan> */
    public function selfServe(): Collection
    {
        return $this->cache->tags(['plan'])->remember(
            'plan:self_serve',
            self::CACHE_TTL,
            fn () => Plan::query()->active()->selfServe()->orderBy('sort_order')->get(),
        );
    }

    public function findBySlug(string $slug): ?Plan
    {
        return Plan::query()->where('slug', $slug)->first();
    }

    public function findOrFail(int $id): Plan
    {
        return Plan::query()->findOrFail($id);
    }

    public function create(array $data): Plan
    {
        $plan = Plan::query()->create($data);
        $this->flush();

        return $plan;
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan->update($data);
        $this->flush();

        return $plan->fresh();
    }

    public function flush(): void
    {
        $this->cache->tags(['plan'])->flush();
    }
}
