<?php

namespace App\Modules\User\Repositories;

use App\Models\User;
use App\Modules\User\Models\LoginHistory;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(User::class, $cache);
    }

    /** @return Collection<int, User> */
    public function getAllForSchool(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:all"),
            fn () => User::with('roles')
                ->forSchool($schoolId)
                ->orderBy('name')
                ->get(),
        );
    }

    public function getPaginated(int $schoolId, int $perPage = 20): LengthAwarePaginator
    {
        return User::with('roles')
            ->forSchool($schoolId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /** @return Collection<int, LoginHistory> */
    public function getLoginHistory(int $userId, int $perPage = 30): LengthAwarePaginator
    {
        return LoginHistory::where('user_id', $userId)
            ->orderByDesc('logged_in_at')
            ->paginate($perPage);
    }

    /** @return LengthAwarePaginator<LoginHistory> */
    public function getAllLoginHistory(int $schoolId, int $perPage = 30): LengthAwarePaginator
    {
        return LoginHistory::where('school_id', $schoolId)
            ->orderByDesc('logged_in_at')
            ->paginate($perPage);
    }

    protected function cacheTag(): string
    {
        return 'users';
    }
}
