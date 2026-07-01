<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Models\LoginHistory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthService
{
    private const MAX_ATTEMPTS    = 3;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    private const EXPIRY_DEFAULT    = 12;  // hours
    private const EXPIRY_REMEMBER   = 90;  // days
    private const EXPIRY_SUPER_ADMIN = 4;  // hours

    // ── Login ─────────────────────────────────────────────────────────────────

    /**
     * @return array{token: string, expires_at: \Carbon\Carbon, user: User}
     */
    public function login(
        string $email,
        string $password,
        bool $rememberMe,
        string $deviceName,
        string $ip,
        string $userAgent,
    ): array {
        $this->enforceLockout($email, $ip);

        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            RateLimiter::hit($this->throttleKey($email, $ip), self::LOCKOUT_SECONDS);

            $this->recordHistory(
                email: $email,
                user: null,
                ip: $ip,
                deviceName: $deviceName,
                userAgent: $userAgent,
                status: 'failed',
                reason: 'Invalid credentials',
            );

            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            $this->recordHistory(
                email: $email,
                user: $user,
                ip: $ip,
                deviceName: $deviceName,
                userAgent: $userAgent,
                status: 'failed',
                reason: 'Account deactivated',
            );

            throw ValidationException::withMessages([
                'email' => ['This account has been deactivated. Contact your administrator.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($email, $ip));

        $role      = $user->getRoleNames()->first() ?? 'student';
        $abilities = User::abilitiesForRole($role);
        $expiresAt = $this->tokenExpiry($role, $rememberMe);

        $newToken = $user->createToken($deviceName, $abilities, $expiresAt);

        $newToken->accessToken->forceFill([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ])->save();

        $history = $this->recordHistory(
            email: $email,
            user: $user,
            ip: $ip,
            deviceName: $deviceName,
            userAgent: $userAgent,
            status: 'success',
        );

        // Store history ID on token so we can stamp logged_out_at on logout
        $newToken->accessToken->forceFill(['name' => $deviceName])->save();

        return [
            'token'      => $newToken->plainTextToken,
            'expires_at' => $expiresAt,
            'user'       => $user->load('roles'),
            'history_id' => $history->id,
        ];
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(User $user): void
    {
        $tokenId = $user->currentAccessToken()?->id;

        $user->currentAccessToken()?->delete();

        // Stamp logged_out_at on the matching history row
        if ($tokenId) {
            LoginHistory::where('user_id', $user->id)
                ->where('status', 'success')
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->first()
                ?->update(['logged_out_at' => now()]);
        }
    }

    public function logoutAll(User $user): void
    {
        $user->tokens()->delete();

        LoginHistory::where('user_id', $user->id)
            ->where('status', 'success')
            ->whereNull('logged_out_at')
            ->update(['logged_out_at' => now()]);
    }

    // ── Devices ───────────────────────────────────────────────────────────────

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PersonalAccessToken>
     */
    public function getDevices(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->tokens()->orderByDesc('last_used_at')->get();
    }

    public function revokeDevice(User $user, int $tokenId): void
    {
        $token = $user->tokens()->findOrFail($tokenId);
        $token->delete();

        LoginHistory::where('user_id', $user->id)
            ->where('status', 'success')
            ->whereNull('logged_out_at')
            ->latest('logged_in_at')
            ->first()
            ?->update(['logged_out_at' => now()]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function throttleKey(string $email, string $ip): string
    {
        return 'login:' . Str::lower($email) . '|' . $ip;
    }

    private function enforceLockout(string $email, string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($email, $ip), self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($email, $ip));

            throw new TooManyRequestsHttpException(
                $seconds,
                "Too many login attempts. Try again in {$seconds} seconds.",
            );
        }
    }

    private function tokenExpiry(string $role, bool $rememberMe): \Carbon\Carbon
    {
        if (in_array($role, ['super_admin', 'admin']) && ! $rememberMe) {
            return now()->addHours(self::EXPIRY_SUPER_ADMIN);
        }

        return $rememberMe
            ? now()->addDays(self::EXPIRY_REMEMBER)
            : now()->addHours(self::EXPIRY_DEFAULT);
    }

    private function recordHistory(
        string $email,
        ?User $user,
        string $ip,
        string $deviceName,
        string $userAgent,
        string $status,
        ?string $reason = null,
    ): LoginHistory {
        return LoginHistory::create([
            'school_id'     => $user?->school_id,
            'user_id'       => $user?->id,
            'email'         => $email,
            'ip_address'    => $ip,
            'device_name'   => $deviceName,
            'user_agent'    => $userAgent,
            'status'        => $status,
            'failed_reason' => $reason,
            'logged_in_at'  => now(),
        ]);
    }
}
