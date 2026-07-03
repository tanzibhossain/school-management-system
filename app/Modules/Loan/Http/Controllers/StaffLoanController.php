<?php

namespace App\Modules\Loan\Http\Controllers;

use App\Modules\Loan\Http\Requests\RejectStaffLoanRequest;
use App\Modules\Loan\Http\Requests\SubmitStaffLoanRequest;
use App\Modules\Loan\Http\Resources\StaffLoanResource;
use App\Modules\Loan\Models\StaffLoan;
use App\Modules\Loan\Repositories\StaffLoanRepository;
use App\Modules\Loan\Services\StaffLoanService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StaffLoanController extends Controller
{
    public function __construct(
        private readonly StaffLoanService $service,
        private readonly StaffLoanRepository $repository,
    ) {}

    /** POST /v2/loans/{staffId} — submit a loan request. */
    public function store(SubmitStaffLoanRequest $request, int $staffId): StaffLoanResource
    {
        $schoolId = app('current_school_id');
        $staff    = Staff::where('school_id', $schoolId)->findOrFail($staffId);

        $loan = $this->service->submit($schoolId, $staff, $request->validated(), $request->user());

        return new StaffLoanResource($loan);
    }

    /** GET /v2/loans/{staffId} — one staff member's loan history, with schedules. */
    public function index(int $staffId): AnonymousResourceCollection
    {
        return StaffLoanResource::collection(
            $this->repository->forStaff(app('current_school_id'), $staffId)
        );
    }

    /** GET /v2/loans/pending — admin/accountant approval queue. */
    public function pending(): AnonymousResourceCollection
    {
        return StaffLoanResource::collection(
            $this->repository->pending(app('current_school_id'))
        );
    }

    /** PATCH /v2/loans/{id}/approve */
    public function approve(Request $request, int $id): StaffLoanResource
    {
        $loan = StaffLoan::forSchool(app('current_school_id'))->findOrFail($id);

        $approved = $this->service->approve($loan, $request->user());

        return new StaffLoanResource($approved->load('schedules'));
    }

    /** PATCH /v2/loans/{id}/reject */
    public function reject(RejectStaffLoanRequest $request, int $id): StaffLoanResource
    {
        $loan = StaffLoan::forSchool(app('current_school_id'))->findOrFail($id);

        $rejected = $this->service->reject($loan, $request->user(), $request->validated('rejection_reason'));

        return new StaffLoanResource($rejected);
    }

    /** PATCH /v2/loans/{id}/cancel */
    public function cancel(Request $request, int $id): StaffLoanResource
    {
        $loan = StaffLoan::forSchool(app('current_school_id'))->findOrFail($id);

        return new StaffLoanResource($this->service->cancel($loan, $request->user()));
    }
}
