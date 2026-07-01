<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Repositories\UserRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;

class UserService extends BaseService
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    public function createUser(array $data, int $schoolId): User
    {
        $role = $data['role'] ?? 'student';

        $user = User::create([
            'school_id' => $schoolId,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $user->assignRole($role);
        $this->repository->flush();

        return $user->load('roles');
    }

    public function updateUser(User $user, array $data): User
    {
        $payload = array_filter([
            'name'      => $data['name'] ?? null,
            'email'     => $data['email'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'avatar'    => $data['avatar'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);
        $this->repository->flush();

        return $user->fresh()->load('roles');
    }

    public function changeRole(User $user, string $role): User
    {
        $user->syncRoles([$role]);

        // Revoke all tokens so the user must log in again with the new role's abilities
        $user->tokens()->delete();
        $this->repository->flush();

        return $user->fresh()->load('roles');
    }

    public function deactivate(User $user): void
    {
        $user->update(['is_active' => false]);
        $user->tokens()->delete();
        $this->repository->flush();
    }

    public function changePassword(User $user, string $newPassword): void
    {
        $user->update(['password' => Hash::make($newPassword)]);
        // Revoke all OTHER tokens (keep current session)
        $user->tokens()->where('id', '!=', $user->currentAccessToken()?->id)->delete();
    }
}
