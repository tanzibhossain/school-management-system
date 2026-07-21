<?php

namespace App\Repositories;

use App\Support\CacheTags;
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
        return $this->cacheTag().':'.$suffix;
    }

    protected function remember(string $key, callable $callback): mixed
    {
        $tag = $this->cacheTag();

        $value = CacheTags::remember([$tag], $key, $this->cacheTtl, $callback);

        // A cache entry serialized under an older class shape — or one that fails
        // to rehydrate (e.g. a serializer/compression mismatch, or a flaky mounted
        // filesystem during class autoload) — deserializes to __PHP_Incomplete_Class.
        // Detect that, drop the poisoned key, and recompute from source so a bad
        // cache entry can never surface as a 500. Fresh values still get re-cached.
        if ($this->isCorruptCacheValue($value)) {
            CacheTags::forget([$tag], $key);
            $value = $callback();
            CacheTags::put([$tag], $key, $value, $this->cacheTtl);
        }

        return $value;
    }

    private function isCorruptCacheValue(mixed $value): bool
    {
        if ($value instanceof \__PHP_Incomplete_Class) {
            return true;
        }

        // Eloquent/Support collections extend Illuminate\Support\Collection; a
        // collection object rehydrates fine even when its *items* did not.
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->contains(static fn ($item): bool => $item instanceof \__PHP_Incomplete_Class);
        }

        return false;
    }

    public function flush(): void
    {
        CacheTags::flush([$this->cacheTag()]);
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
