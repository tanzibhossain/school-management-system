<?php

namespace App\Modules\User\Http\Controllers;

use App\Modules\User\Http\Requests\ChangePasswordRequest;
use App\Modules\User\Http\Requests\LoginRequest;
use App\Modules\User\Http\Resources\DeviceResource;
use App\Modules\User\Http\Resources\LoginHistoryResource;
use App\Modules\User\Http\Resources\UserResource;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Services\AuthService;
use App\Modules\User\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserService $userService,
        private readonly UserRepository $repository,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->input('email'),
            password: $request->input('password'),
            rememberMe: (bool) $request->input('remember_me', false),
            deviceName: $request->input('device_name') ?? $this->parseDeviceName($request),
            ip: $request->ip(),
            userAgent: $request->userAgent() ?? '',
        );

        return response()->json([
            'token' => $result['token'],
            'expires_at' => $result['expires_at'],
            'user' => new UserResource($result['user']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAll($request->user());

        return response()->json(['message' => 'Logged out from all devices.']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->userService->changePassword($request->user(), $request->input('password'));

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function devices(Request $request): AnonymousResourceCollection
    {
        $devices = $this->authService->getDevices($request->user());

        return DeviceResource::collection($devices);
    }

    public function revokeDevice(Request $request, int $tokenId): JsonResponse
    {
        $this->authService->revokeDevice($request->user(), $tokenId);

        return response()->json(['message' => 'Device revoked.']);
    }

    public function loginHistory(Request $request): AnonymousResourceCollection
    {
        $history = $this->repository->getLoginHistory($request->user()->id);

        return LoginHistoryResource::collection($history);
    }

    private function parseDeviceName(Request $request): string
    {
        $ua = $request->userAgent() ?? 'Unknown';

        if (str_contains($ua, 'Mobile') || str_contains($ua, 'Android')) {
            return 'Mobile Device';
        }

        if (str_contains($ua, 'curl') || str_contains($ua, 'Postman')) {
            return 'API Client';
        }

        return 'Web Browser';
    }
}
