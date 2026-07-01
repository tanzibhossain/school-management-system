<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseService
{
    public function __construct(
        protected BaseRepository $repository,
    ) {}

    public function all(int $schoolId): Collection
    {
        return $this->repository->all($schoolId);
    }

    public function find(int $id, int $schoolId): ?Model
    {
        return $this->repository->find($id, $schoolId);
    }

    public function findOrFail(int $id, int $schoolId): Model
    {
        return $this->repository->findOrFail($id, $schoolId);
    }

    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        return $this->repository->update($model, $data);
    }

    public function delete(Model $model): bool
    {
        return $this->repository->delete($model);
    }
}
