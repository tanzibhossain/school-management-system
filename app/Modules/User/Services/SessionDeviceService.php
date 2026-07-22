<?php

namespace App\Modules\User\Services;

use App\Models\User;
use App\Modules\User\Models\LoginHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Device/session tracking for the session-based Blade portals (admin/staff/
 * family) — the counterpart to AuthService's Sanctum-token device list for the
 * API. There's no token to key a "device" off here, so the actual session ID
 * is stored on the login_histories row instead; revoking a session means
 * destroying that exact session from the session store (Redis) via Laravel's
 * own session handler, never a hand-rolled Redis key.
 */
class SessionDeviceService
{
    public function recordLogin(User $user, Request $request): LoginHistory
    {
        return LoginHistory::create([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'device_name' => $this->deviceName($request->userAgent() ?? ''),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()->getId(),
            'status' => 'success',
            'logged_in_at' => now(),
        ]);
    }

    /** Stamp logged_out_at for the current request's session, if it was tracked. */
    public function recordLogout(Request $request): void
    {
        LoginHistory::where('session_id', $request->session()->getId())
            ->whereNull('logged_out_at')
            ->update(['logged_out_at' => now()]);
    }

    /** @return Collection<int, LoginHistory> active (not-logged-out) sessions, newest first. */
    public function activeSessions(User $user): Collection
    {
        return LoginHistory::where('user_id', $user->id)
            ->where('status', 'success')
            ->whereNull('logged_out_at')
            ->whereNotNull('session_id')
            ->orderByDesc('logged_in_at')
            ->get();
    }

    /** Revoke one specific session by its login_histories row id. */
    public function revokeSession(User $user, int $historyId): bool
    {
        $history = LoginHistory::where('user_id', $user->id)
            ->where('id', $historyId)
            ->whereNull('logged_out_at')
            ->first();

        if (! $history || ! $history->session_id) {
            return false;
        }

        $this->destroySession($history->session_id);
        $history->update(['logged_out_at' => now()]);

        return true;
    }

    /** Revoke every active session for this user except the current one. Returns count revoked. */
    public function revokeOtherSessions(User $user, string $currentSessionId): int
    {
        $others = $this->activeSessions($user)->where('session_id', '!=', $currentSessionId);

        foreach ($others as $history) {
            $this->destroySession($history->session_id);
        }

        LoginHistory::whereIn('id', $others->pluck('id'))->update(['logged_out_at' => now()]);

        return $others->count();
    }

    /** Force-expire a session by ID via Laravel's own session handler (driver-agnostic). */
    private function destroySession(string $sessionId): void
    {
        App::make('session')->driver()->getHandler()->destroy($sessionId);
    }

    /** Very small UA sniff — good enough for "which of my devices is this", not analytics. */
    private function deviceName(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'CriOS') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari',
            default => 'Unknown browser',
        };

        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'iPhone') => 'iPhone',
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'Mac OS X') => 'Mac',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        return "{$browser} on {$os}";
    }
}
