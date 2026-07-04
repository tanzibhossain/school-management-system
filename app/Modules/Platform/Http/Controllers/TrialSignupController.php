<?php

namespace App\Modules\Platform\Http\Controllers;

use App\Modules\Platform\Http\Requests\StoreTrialSignupRequest;
use App\Modules\Platform\Http\Resources\SchoolAdminResource;
use App\Modules\Platform\Services\SelfServeSignupService;
use Illuminate\Routing\Controller;

class TrialSignupController extends Controller
{
    public function __construct(private readonly SelfServeSignupService $service) {}

    /** POST /v2/platform/signup/trial — public. Free, provisions immediately. */
    public function store(StoreTrialSignupRequest $request): SchoolAdminResource
    {
        $school = $this->service->trial($request->validated());

        return new SchoolAdminResource($school);
    }
}
