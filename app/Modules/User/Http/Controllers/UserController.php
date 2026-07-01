<?php

namespace App\Modules\User\Http\Controllers;

use App\Models\User;
use App\Modules\User\Http\Requests\StoreUserRequest;
use App\Modules\User\Http\Requests\UpdateUserRequest;
use App\Modules\User\Http\Resources\LoginHistoryResource;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService    $service,
        private readonly UserRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = $this->repository->getPaginated(app('current_school_id'));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = $this->service->createUser($request->validated(), app('current_school_id'));

        return new UserResource($user);
    }

    public function show(int $id): UserResource
    {
        $user = User::forSchool(app('current_school_id'))->with('roles')->findOrFail($id);

        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, int $id): UserResource
    {
        $user    = User::forSchool(app('current_school_id'))->findOrFail($id);
        $updated = $this->service->updateUser($user, $request->validated());

        return new UserResource($updated);
    }

    public function changeRole(Request $request, int $id): UserResource
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin','teacher','accountant','librarian','receptionist','student','parent'])],
        ]);

        $user    = User::forSchool(app('current_school_id'))->findOrFail($id);
        $updated = $this->service->changeRole($user, $request->input('role'));

        return new UserResource($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::forSchool(app('current_school_id'))->findOrFail($id);
        $this->service->deactivate($user);

        return response()->json(['message' => 'User deactivated.']);
    }

    public function loginHistory(int $id): AnonymousResourceCollection
    {
        User::forSchool(app('current_school_id'))->findOrFail($id);
        $history = $this->repository->getLoginHistory($id);

        return LoginHistoryResource::collection($history);
    }

    public function allLoginHistory(Request $request): AnonymousResourceCollection
    {
        $history = $this->repository->getAllLoginHistory(app('current_school_id'));

        return LoginHistoryResource::collection($history);
    }
}
