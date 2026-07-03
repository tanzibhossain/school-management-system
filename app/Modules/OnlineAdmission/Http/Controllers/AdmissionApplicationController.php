<?php

namespace App\Modules\OnlineAdmission\Http\Controllers;

use App\Modules\OnlineAdmission\Http\Requests\ApproveAdmissionApplicationRequest;
use App\Modules\OnlineAdmission\Http\Requests\RejectAdmissionApplicationRequest;
use App\Modules\OnlineAdmission\Http\Requests\SubmitAdmissionApplicationRequest;
use App\Modules\OnlineAdmission\Http\Resources\AdmissionApplicationResource;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\OnlineAdmission\Repositories\AdmissionApplicationRepository;
use App\Modules\OnlineAdmission\Services\AdmissionApplicationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class AdmissionApplicationController extends Controller
{
    public function __construct(
        private readonly AdmissionApplicationService $service,
        private readonly AdmissionApplicationRepository $repository,
    ) {}

    /** POST /v2/admission-applications — public, no login. */
    public function store(SubmitAdmissionApplicationRequest $request): AdmissionApplicationResource
    {
        $schoolId = app('current_school_id');

        $application = $this->service->submit($schoolId, $request->validated());

        // submit() returns the just-created instance directly (no fresh()
        // re-fetch), so wasRecentlyCreated stays true and Laravel's automatic
        // 201 status applies without needing to force it.
        return new AdmissionApplicationResource($application);
    }

    /**
     * GET /v2/admission-applications/status — public, no login. Reference alone
     * is guessable/sequential, so guardian_phone must match too.
     */
    public function status(Request $request): AdmissionApplicationResource
    {
        $data = Validator::make($request->query(), [
            'reference' => ['required', 'string'],
            'guardian_phone' => ['required', 'string'],
        ])->validate();

        $schoolId = app('current_school_id');
        $application = $this->service->checkStatus($schoolId, $data['reference'], $data['guardian_phone']);

        abort_if(! $application, 404, 'Application not found.');

        return new AdmissionApplicationResource($application);
    }

    /** GET /v2/admission-applications — admin only, review queue. */
    public function index(): AnonymousResourceCollection
    {
        return AdmissionApplicationResource::collection(
            $this->repository->forSchool(app('current_school_id'))
        );
    }

    /** GET /v2/admission-applications/{id} — admin only. */
    public function show(int $id): AdmissionApplicationResource
    {
        $application = AdmissionApplication::forSchool(app('current_school_id'))
            ->with(['desiredClass', 'desiredAcademicYear', 'student'])
            ->findOrFail($id);

        return new AdmissionApplicationResource($application);
    }

    /** POST /v2/admission-applications/{id}/approve — admin only, enrols in the same action. */
    public function approve(ApproveAdmissionApplicationRequest $request, int $id): AdmissionApplicationResource
    {
        $application = AdmissionApplication::forSchool(app('current_school_id'))->findOrFail($id);

        $application = $this->service->approve($application, $request->user(), $request->validated());

        return new AdmissionApplicationResource($application);
    }

    /** POST /v2/admission-applications/{id}/reject — admin only. */
    public function reject(RejectAdmissionApplicationRequest $request, int $id): AdmissionApplicationResource
    {
        $application = AdmissionApplication::forSchool(app('current_school_id'))->findOrFail($id);

        $application = $this->service->reject($application, $request->user(), $request->validated('reason'));

        return new AdmissionApplicationResource($application);
    }
}
