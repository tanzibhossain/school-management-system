<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Modules\Payroll\Http\Requests\ApprovePayrollRunRequest;
use App\Modules\Payroll\Http\Requests\ProcessPayrollRunRequest;
use App\Modules\Payroll\Http\Requests\StorePayrollRunRequest;
use App\Modules\Payroll\Http\Resources\PayrollRunResource;
use App\Modules\Payroll\Models\PayrollRun;
use App\Modules\Payroll\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PayrollRunController extends Controller
{
    public function __construct(private readonly PayrollService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return PayrollRunResource::collection($this->service->forSchool(app('current_school_id')));
    }

    public function store(StorePayrollRunRequest $request): JsonResponse
    {
        $run = $this->service->createRun(
            app('current_school_id'),
            $request->validated('month'),
            $request->validated('year'),
            $request->validated('notes'),
        );

        return (new PayrollRunResource($run))->response()->setStatusCode(201);
    }

    public function show(int $id): PayrollRunResource
    {
        $run = PayrollRun::forSchool(app('current_school_id'))->with('entries.staff')->findOrFail($id);

        return new PayrollRunResource($run);
    }

    public function process(ProcessPayrollRunRequest $request, int $id): PayrollRunResource
    {
        $run = $this->service->processRun(app('current_school_id'), $id, $request->user());

        return new PayrollRunResource($run->load('entries.staff'));
    }

    public function approve(ApprovePayrollRunRequest $request, int $id): PayrollRunResource
    {
        $run = $this->service->approveRun(app('current_school_id'), $id, $request->user());

        return new PayrollRunResource($run->load('entries.staff'));
    }
}
