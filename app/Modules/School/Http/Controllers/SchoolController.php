<?php

namespace App\Modules\School\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\School\Http\Requests\SyncPhonesRequest;
use App\Modules\School\Http\Requests\UpdateOpeningHourRequest;
use App\Modules\School\Http\Requests\UpdateSchoolRequest;
use App\Modules\School\Http\Resources\SchoolOpeningHourResource;
use App\Modules\School\Http\Resources\SchoolResource;
use App\Modules\School\Models\School;
use App\Modules\School\Services\SchoolService;
use Illuminate\Http\JsonResponse;

class SchoolController extends Controller
{
    public function __construct(private readonly SchoolService $service) {}

    /**
     * GET /api/v2/school — full profile with phones and opening hours.
     */
    public function show(): SchoolResource
    {
        return new SchoolResource($this->service->getSettings());
    }

    /**
     * PUT /api/v2/school — update main school profile fields.
     */
    public function update(UpdateSchoolRequest $request): SchoolResource
    {
        $school = School::current();

        return new SchoolResource(
            $this->service->updateSettings($school, $request->validated())
        );
    }

    /**
     * POST /api/v2/school/phones/sync — replace the full phone list.
     */
    public function syncPhones(SyncPhonesRequest $request): JsonResponse
    {
        $school = School::current();
        $this->service->syncPhones($school->id, $request->validated('phones'));

        return response()->json(['message' => 'Phones updated successfully.']);
    }

    /**
     * PUT /api/v2/school/hours/{day} — update one day's opening hours (0=Sun … 6=Sat).
     */
    public function updateHour(UpdateOpeningHourRequest $request, int $day): SchoolOpeningHourResource
    {
        $school = School::current();
        $hour = $this->service->updateOpeningHour($school->id, $day, $request->validated());

        return new SchoolOpeningHourResource($hour);
    }

    /**
     * GET /api/v2/public/school — public endpoint, no auth required.
     * Returns name, logo, phones, and opening hours only.
     */
    public function publicProfile(): SchoolResource
    {
        return new SchoolResource($this->service->getSettings());
    }
}
