<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\TransportVehicle;
use App\Modules\Transport\Repositories\TransportVehicleRepository;
use App\Services\BaseService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TransportVehicleService extends BaseService
{
    public function __construct(TransportVehicleRepository $repository)
    {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function make(int $schoolId, array $data): TransportVehicle
    {
        $data['school_id'] = $schoolId;
        $vehicle = TransportVehicle::create($data);
        $this->repository->flush();

        return $vehicle;
    }

    /** @param array<string, mixed> $data */
    public function modify(TransportVehicle $vehicle, array $data): TransportVehicle
    {
        $vehicle->update($data);
        $this->repository->flush();

        return $vehicle->fresh();
    }

    /**
     * Direct status change (e.g. repairing an out_of_service vehicle back into the
     * pool). A vehicle currently serving a route (in_service) cannot be hand-edited
     * to another status here -- detach/swap it via the route first.
     */
    public function changeStatus(TransportVehicle $vehicle, string $status): TransportVehicle
    {
        if ($vehicle->status === 'in_service') {
            throw new UnprocessableEntityHttpException(
                'This vehicle is in service on a route. Detach or swap it from the route first.'
            );
        }

        $vehicle->update(['status' => $status]);
        $this->repository->flush();

        return $vehicle->fresh();
    }

    /** Delete a vehicle -- blocked while it is actively serving a route. */
    public function remove(TransportVehicle $vehicle): void
    {
        if ($vehicle->status === 'in_service') {
            throw new UnprocessableEntityHttpException(
                'This vehicle is in service on a route. Detach or swap it from the route first.'
            );
        }

        $vehicle->delete();
        $this->repository->flush();
    }
}
