<?php

namespace App\Repositories;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    protected int $cacheTtl = 3600; // 1 hour

    public function __construct(
        protected string $modelClass,
        protected CacheRepository $cache,
    ) {
        $this->model = app($modelClass);
    }

    // ─── Cache helpers ────────────────────────────────────────────────────────

    protected function cacheTag(): string
    {
        return strtolower(class_basename($this->modelClass));
    }

    protected function cacheKey(string $suffix): string
    {
        return $this->cacheTag() . ':' . $suffix;
    }

    protected function remember(string $key, callable $callback): mixed
    {
        return $this->cache->tags([$this->cacheTag()])->remember(
            $key,
            $this->cacheTtl,
            $callback,
        );
    }

    protected function flush(): void
    {
        $this->cache->tags([$this->cacheTag()])->flush();
    }

    // ─── Base CRUD ────────────────────────────────────────────────────────────

    public function all(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all"),
            fn () => $this->model->newQuery()->where('school_id', $schoolId)->get(),
        );
    }

    public function find(int $id, int $schoolId): ?Model
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:id:{$id}"),
            fn () => $this->model->newQuery()
                ->where('school_id', $schoolId)
                ->find($id),
        );
    }

    public function findOrFail(int $id, int $schoolId): Model
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:id:{$id}"),
            fn () => $this->model->newQuery()
                ->where('school_id', $schoolId)
                ->findOrFail($id),
        );
    }

    public function create(array $data): Model
    {
        $record = $this->model->newQuery()->create($data);
        $this->flush();

        return $record;
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);
        $this->flush();

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        $result = (bool) $model->delete();
        $this->flush();

        return $result;
    }
}
