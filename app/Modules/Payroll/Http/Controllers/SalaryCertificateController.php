<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Modules\Payroll\Http\Requests\GenerateSalaryCertificateRequest;
use App\Modules\Payroll\Http\Requests\RequestSalaryCertificateRequest;
use App\Modules\Payroll\Http\Resources\SalaryCertificateRequestResource;
use App\Modules\Payroll\Models\SalaryCertificateRequest;
use App\Modules\Payroll\Repositories\SalaryCertificateRequestRepository;
use App\Modules\Payroll\Services\SalaryCertificateService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SalaryCertificateController extends Controller
{
    public function __construct(
        private readonly SalaryCertificateService $service,
        private readonly SalaryCertificateRequestRepository $repository,
    ) {}

    /** POST /v2/payroll/salary-certificate — self-service, staff requests their OWN certificate. */
    public function store(RequestSalaryCertificateRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->where('user_id', $request->user()->id)->firstOrFail();

        $certificateRequest = $this->service->request($schoolId, $staff->id, $request->validated('purpose'));

        return (new SalaryCertificateRequestResource($certificateRequest))->response()->setStatusCode(201);
    }

    /** GET /v2/payroll/salary-certificate — admin/accountant: pending requests. */
    public function index(): AnonymousResourceCollection
    {
        return SalaryCertificateRequestResource::collection(
            $this->repository->pendingForSchool(app('current_school_id'))
        );
    }

    /** POST /v2/payroll/salary-certificate/{id}/generate — admin/accountant. */
    public function generate(GenerateSalaryCertificateRequest $request, int $id): SalaryCertificateRequestResource
    {
        $schoolId = app('current_school_id');
        $certificateRequest = SalaryCertificateRequest::forSchool($schoolId)->findOrFail($id);

        return new SalaryCertificateRequestResource(
            $this->service->generate($schoolId, $certificateRequest, $request->user())
        );
    }

    /** GET /v2/payroll/staff/me/certificates — self-service, own record only. */
    public function myCertificates(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->where('user_id', $request->user()->id)->firstOrFail();

        return SalaryCertificateRequestResource::collection($this->repository->forStaff($schoolId, $staff->id));
    }
}
