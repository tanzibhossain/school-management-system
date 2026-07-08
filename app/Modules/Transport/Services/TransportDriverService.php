<?php

namespace App\Modules\Transport\Services;

use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Repositories\TransportDriverRepository;
use App\Services\BaseService;

class TransportDriverService extends BaseService
{
    public function __construct(TransportDriverRepository $repository)
    {
        parent::__construct($repository);
    }

    /** @param array<string, mixed> $data */
    public function make(int $schoolId, array $data): TransportDriver
    {
        $data['school_id'] = $schoolId;
        $driver = TransportDriver::create($data);
        $this->repository->flush();

        return $driver;
    }

    /** @param array<string, mixed> $data */
    public function modify(TransportDriver $driver, array $data): TransportDriver
    {
        $driver->update($data);
        $this->repository->flush();

        return $driver->fresh();
    }
}
