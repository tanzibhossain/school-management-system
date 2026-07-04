<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Modules\Payroll\Http\Requests\StoreSalaryComponentRequest;
use App\Modules\Payroll\Http\Requests\TrashSalaryComponentRequest;
use App\Modules\Payroll\Http\Requests\UpdateSalaryComponentRequest;
use App\Modules\Payroll\Http\Resources\SalaryComponentResource;
use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\Payroll\Services\SalaryComponentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SalaryComponentController extends Controller
{
    public function __construct(private readonly SalaryComponentService $service) {}

    public function index(): AnonymousResourceCollection
    {
        return SalaryComponentResource::collection($this->service->forSchool(app('current_school_id')));
    }

    public function store(StoreSalaryComponentRequest $request): JsonResponse
    {
        $component = $this->service->create(app('current_school_id'), $request->validated());

        return (new SalaryComponentResource($component))->response()->setStatusCode(201);
    }

    public function update(UpdateSalaryComponentRequest $request, int $id): SalaryComponentResource
    {
        $component = SalaryComponent::forSchool(app('current_school_id'))->findOrFail($id);
        $component = $this->service->update($component, $request->validated());

        return new SalaryComponentResource($component);
    }

    public function destroy(TrashSalaryComponentRequest $request, int $id): SalaryComponentResource
    {
        $component = SalaryComponent::forSchool(app('current_school_id'))->findOrFail($id);
        $component = $this->service->trash($component);

        return new SalaryComponentResource($component);
    }
}
