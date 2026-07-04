<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Modules\Platform\Http\Resources\PlanResource;
use App\Modules\Platform\Services\PlanService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PublicPlanController extends Controller
{
    public function __construct(private readonly PlanService $service) {}

    /** GET /v2/platform/plans — public. Self-serve only; Demo never appears here. */
    public function index(): AnonymousResourceCollection
    {
        return PlanResource::collection($this->service->selfServe());
    }
}
