<?php

namespace App\Modules\Transport\Http\Controllers;

use App\Modules\Transport\Http\Requests\StoreTransportDriverRequest;
use App\Modules\Transport\Http\Requests\UpdateTransportDriverRequest;
use App\Modules\Transport\Http\Resources\TransportDriverResource;
use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Repositories\TransportDriverRepository;
use App\Modules\Transport\Services\TransportDriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TransportDriverController extends Controller
{
    public function __construct(
        private readonly TransportDriverService $service,
        private readonly TransportDriverRepository $repository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return TransportDriverResource::collection($this->repository->all(app('current_school_id')));
    }

    public function store(StoreTransportDriverRequest $request): JsonResponse
    {
        $driver = $this->service->make(app('current_school_id'), $request->validated());

        return (new TransportDriverResource($driver))->response()->setStatusCode(201);
    }

    public function show(int $id): TransportDriverResource
    {
        $driver = TransportDriver::forSchool(app('current_school_id'))->findOrFail($id);

        return new TransportDriverResource($driver);
    }

    public function update(UpdateTransportDriverRequest $request, int $id): TransportDriverResource
    {
        $driver = TransportDriver::forSchool(app('current_school_id'))->findOrFail($id);

        return new TransportDriverResource($this->service->modify($driver, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $driver = TransportDriver::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->delete($driver);

        return response()->json(['message' => 'Driver deleted.']);
    }
}
