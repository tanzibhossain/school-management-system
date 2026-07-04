<?php

namespace App\Modules\Platform\Services;

use App\Modules\Platform\Models\Plan;
use App\Modules\Platform\Repositories\PlanRepository;
use Illuminate\Database\Eloquent\Collection;

class PlanService
{
    public function __construct(private readonly PlanRepository $repository) {}

    /** @return Collection<int, Plan> */
    public function allActive(): Collection
    {
        return $this->repository->allActive();
    }

    /** Public-facing plan list — self-serve only, Demo never appears here. */
    public function selfServe(): Collection
    {
        return $this->repository->selfServe();
    }

    public function findBySlugOrFail(string $slug): Plan
    {
        $plan = $this->repository->findBySlug($slug);

        if (! $plan) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Plan '{$slug}' not found.");
        }

        return $plan;
    }

    public function findOrFail(int $id): Plan
    {
        return $this->repository->findOrFail($id);
    }

    public function create(array $data): Plan
    {
        return $this->repository->create($data);
    }

    public function update(Plan $plan, array $data): Plan
    {
        return $this->repository->update($plan, $data);
    }
}
