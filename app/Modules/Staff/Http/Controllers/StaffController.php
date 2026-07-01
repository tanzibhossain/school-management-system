<?php

namespace App\Modules\Staff\Http\Controllers;

use App\Modules\Staff\Http\Requests\ReHireStaffRequest;
use App\Modules\Staff\Http\Requests\StoreStaffRequest;
use App\Modules\Staff\Http\Requests\UpdateStaffRequest;
use App\Modules\Staff\Http\Resources\StaffListResource;
use App\Modules\Staff\Http\Resources\StaffResource;
use App\Modules\Staff\Repositories\StaffRepository;
use App\Modules\Staff\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $service,
        private readonly StaffRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $staff = $this->repository->paginate(
            app('current_school_id'),
            $request->only(['status', 'designation_id', 'department_id', 'employment_type', 'search']),
        );

        return StaffListResource::collection($staff);
    }

    public function store(StoreStaffRequest $request): JsonResponse
    {
        $staff = $this->service->hire(app('current_school_id'), $request->validated());

        if ($request->hasFile('photo')) {
            $staff = $this->service->uploadPhoto($staff, $request->file('photo'));
        }

        return (new StaffResource($staff->load(['designation', 'department'])))->response()->setStatusCode(201);
    }

    public function show(int $id): StaffResource
    {
        $staff = $this->repository->findOrFail($id, app('current_school_id'));

        return new StaffResource($staff->load([
            'designation', 'department',
            'academics', 'addresses', 'experiences', 'documents',
        ]));
    }

    public function update(UpdateStaffRequest $request, int $id): StaffResource
    {
        $staff = $this->repository->findOrFail($id, app('current_school_id'));

        if ($request->hasFile('photo')) {
            $staff = $this->service->uploadPhoto($staff, $request->file('photo'));
        }

        $staff = $this->service->update($staff, $request->except('photo'));

        return new StaffResource($staff->load(['designation', 'department']));
    }

    public function terminate(int $id): StaffResource
    {
        $staff = $this->repository->findOrFail($id, app('current_school_id'));
        $staff = $this->service->terminate($staff);

        return new StaffResource($staff->load(['designation', 'department']));
    }

    public function reHire(ReHireStaffRequest $request, int $id): StaffResource
    {
        $staff = $this->repository->findOrFail($id, app('current_school_id'));
        $staff = $this->service->reHire($staff, $request->validated());

        return new StaffResource($staff->load(['designation', 'department']));
    }

    public function destroy(int $id): JsonResponse
    {
        $staff = $this->repository->findOrFail($id, app('current_school_id'));
        $this->service->trash($staff);

        return response()->json(['message' => 'Staff member deleted.']);
    }
}
