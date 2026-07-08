<?php

namespace App\Modules\Transport\Http\Controllers;

use App\Modules\Transport\Http\Requests\ChangeVehicleStatusRequest;
use App\Modules\Transport\Http\Requests\StoreTransportVehicleRequest;
use App\Modules\Transport\Http\Requests\UpdateTransportVehicleRequest;
use App\Modules\Transport\Http\Resources\TransportVehicleResource;
use App\Modules\Transport\Models\TransportVehicle;
use App\Modules\Transport\Repositories\TransportVehicleRepository;
use App\Modules\Transport\Services\TransportVehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TransportVehicleController extends Controller
{
    public function __construct(
        private readonly TransportVehicleService $service,
        private readonly TransportVehicleRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $vehicles = $request->query('status') === 'available'
            ? $this->repository->available($schoolId)
            : $this->repository->all($schoolId);

        return TransportVehicleResource::collection($vehicles);
    }

    public function store(StoreTransportVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->service->make(app('current_school_id'), $request->validated());

        return (new TransportVehicleResource($vehicle))->response()->setStatusCode(201);
    }

    public function show(int $id): TransportVehicleResource
    {
        $vehicle = TransportVehicle::forSchool(app('current_school_id'))->findOrFail($id);

        return new TransportVehicleResource($vehicle);
    }

    public function update(UpdateTransportVehicleRequest $request, int $id): TransportVehicleResource
    {
        $vehicle = TransportVehicle::forSchool(app('current_school_id'))->findOrFail($id);

        return new TransportVehicleResource($this->service->modify($vehicle, $request->validated()));
    }

    public function changeStatus(ChangeVehicleStatusRequest $request, int $id): TransportVehicleResource
    {
        $vehicle = TransportVehicle::forSchool(app('current_school_id'))->findOrFail($id);

        return new TransportVehicleResource(
            $this->service->changeStatus($vehicle, $request->validated()['status'])
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $vehicle = TransportVehicle::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->remove($vehicle);

        return response()->json(['message' => 'Vehicle deleted.']);
    }
}
