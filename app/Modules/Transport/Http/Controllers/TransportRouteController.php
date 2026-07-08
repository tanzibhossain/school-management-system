<?php

namespace App\Modules\Transport\Http\Controllers;

use App\Modules\Transport\Http\Requests\SetRouteVehicleRequest;
use App\Modules\Transport\Http\Requests\StoreTransportRouteRequest;
use App\Modules\Transport\Http\Requests\SwapVehicleRequest;
use App\Modules\Transport\Http\Requests\UpdateTransportRouteRequest;
use App\Modules\Transport\Http\Resources\StudentTransportAssignmentResource;
use App\Modules\Transport\Http\Resources\TransportRouteResource;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Repositories\StudentTransportAssignmentRepository;
use App\Modules\Transport\Repositories\TransportRouteRepository;
use App\Modules\Transport\Services\TransportRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TransportRouteController extends Controller
{
    public function __construct(
        private readonly TransportRouteService $service,
        private readonly TransportRouteRepository $repository,
        private readonly StudentTransportAssignmentRepository $assignments,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return TransportRouteResource::collection(
            $this->repository->allWithRelations(app('current_school_id'))
        );
    }

    public function store(StoreTransportRouteRequest $request): JsonResponse
    {
        $route = $this->service->make(app('current_school_id'), $request->validated());

        return (new TransportRouteResource($route))->response()->setStatusCode(201);
    }

    public function show(int $id): TransportRouteResource
    {
        $route = TransportRoute::forSchool(app('current_school_id'))->with(['vehicle', 'driver'])->findOrFail($id);

        return new TransportRouteResource($route);
    }

    public function update(UpdateTransportRouteRequest $request, int $id): TransportRouteResource
    {
        $route = TransportRoute::forSchool(app('current_school_id'))->findOrFail($id);

        return new TransportRouteResource($this->service->modify($route, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $route = TransportRoute::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->update($route, ['is_active' => false]);

        return response()->json(['message' => 'Route deactivated.']);
    }

    public function setVehicle(SetRouteVehicleRequest $request, int $id): TransportRouteResource
    {
        $route = $this->service->setVehicle(
            app('current_school_id'),
            $id,
            $request->validated()['vehicle_id'],
        );

        return new TransportRouteResource($route);
    }

    public function swapVehicle(SwapVehicleRequest $request, int $id): TransportRouteResource
    {
        $route = $this->service->swapVehicle(
            app('current_school_id'),
            $id,
            $request->validated()['replacement_vehicle_id'],
            $request->user()?->id,
        );

        return new TransportRouteResource($route);
    }

    public function roster(int $id): AnonymousResourceCollection
    {
        $rows = $this->assignments->filtered(app('current_school_id'), ['route_id' => $id, 'status' => 'active']);

        return StudentTransportAssignmentResource::collection($rows);
    }
}
