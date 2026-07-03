<?php

namespace App\Modules\Leave\Http\Controllers;

use App\Modules\Leave\Http\Requests\StoreLeaveTypeRequest;
use App\Modules\Leave\Http\Requests\UpdateLeaveTypeRequest;
use App\Modules\Leave\Http\Resources\LeaveTypeResource;
use App\Modules\Leave\Repositories\LeaveTypeRepository;
use App\Modules\Leave\Services\LeaveTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class LeaveTypeController extends Controller
{
    public function __construct(
        private readonly LeaveTypeService $service,
        private readonly LeaveTypeRepository $repository,
    ) {}

    /** GET /v2/leave/types?applies_to= — pass applies_to (student|staff) to filter to active types usable by that person. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');

        $types = $request->query('applies_to')
            ? $this->repository->activeFor($schoolId, $request->query('applies_to'))
            : $this->service->all($schoolId);

        return LeaveTypeResource::collection($types);
    }

    public function store(StoreLeaveTypeRequest $request): LeaveTypeResource
    {
        $type = $this->service->create($request->validated() + ['school_id' => app('current_school_id')]);

        return new LeaveTypeResource($type);
    }

    public function update(UpdateLeaveTypeRequest $request, int $id): LeaveTypeResource
    {
        $type = $this->service->findOrFail($id, app('current_school_id'));

        return new LeaveTypeResource($this->service->update($type, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $type = $this->service->findOrFail($id, app('current_school_id'));
        $this->service->delete($type);

        return response()->json(['message' => 'Leave type deleted.']);
    }
}
