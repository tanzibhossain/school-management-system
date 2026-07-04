<?php

namespace App\Modules\Platform\Http\Controllers\SuperAdmin;

use App\Modules\Platform\Http\Requests\StorePlanRequest;
use App\Modules\Platform\Http\Requests\UpdatePlanRequest;
use App\Modules\Platform\Http\Resources\PlanResource;
use App\Modules\Platform\Models\Plan;
use App\Modules\Platform\Services\PlanService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/** Gated by `role:super_admin` at the route level. */
class PlanController extends Controller
{
    public function __construct(private readonly PlanService $service) {}

    /** GET /v2/platform/admin/plans — every plan, including inactive ones. */
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection(Plan::query()->orderBy('sort_order')->get());
    }

    /** POST /v2/platform/admin/plans */
    public function store(StorePlanRequest $request): PlanResource
    {
        return new PlanResource($this->service->create($request->validated()));
    }

    /** PUT /v2/platform/admin/plans/{plan} */
    public function update(UpdatePlanRequest $request, Plan $plan): PlanResource
    {
        return new PlanResource($this->service->update($plan, $request->validated()));
    }
}
