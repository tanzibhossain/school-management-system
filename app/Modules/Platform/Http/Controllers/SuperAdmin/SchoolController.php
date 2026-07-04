<?php

namespace App\Modules\Platform\Http\Controllers\SuperAdmin;

use App\Modules\Platform\Http\Requests\StoreOfflineSchoolRequest;
use App\Modules\Platform\Http\Requests\UpdateSchoolPlanRequest;
use App\Modules\Platform\Http\Resources\SchoolAdminResource;
use App\Modules\Platform\Services\SuperAdminSchoolService;
use App\Modules\School\Models\School;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

/** Gated by `role:super_admin` at the route level — every school, no current_school_id scoping. */
class SchoolController extends Controller
{
    public function __construct(private readonly SuperAdminSchoolService $service) {}

    /** GET /v2/platform/admin/schools */
    public function index(): AnonymousResourceCollection
    {
        return SchoolAdminResource::collection($this->service->all());
    }

    /** POST /v2/platform/admin/schools — offline/manual creation, no Stripe. */
    public function store(StoreOfflineSchoolRequest $request): SchoolAdminResource
    {
        $school = $this->service->createOffline(
            $request->validated(),
            $request->validated('plan_id'),
            $request->date('subscription_expires_at'),
        );

        return new SchoolAdminResource($school);
    }

    /** GET /v2/platform/admin/schools/{id} */
    public function show(int $id): SchoolAdminResource
    {
        return new SchoolAdminResource(School::with('plan')->findOrFail($id));
    }

    /** PATCH /v2/platform/admin/schools/{id}/plan */
    public function updatePlan(UpdateSchoolPlanRequest $request, int $id): SchoolAdminResource
    {
        $school = School::findOrFail($id);

        $updated = $this->service->changePlan(
            $school,
            $request->validated('plan_id'),
            $request->validated('subscription_expires_at') ? $request->date('subscription_expires_at') : null,
        );

        // changePlan() returns fresh('plan') — an updated, not freshly-created,
        // model, so Laravel's automatic 200 already applies (no gotcha here).
        return new SchoolAdminResource($updated);
    }
}
