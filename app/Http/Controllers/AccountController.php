<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Modules\User\Services\AccountService;
use App\Modules\User\Services\SessionDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Self-service account settings — name, password, email (held pending until
 * confirmed), two-factor auth, and active sessions. One controller shared by
 * the admin/staff/family portals (registered under each prefix's route group);
 * the portal is read off the current route name so the right layout renders
 * and mutation actions can just redirect back to wherever they were called from.
 */
class AccountController extends Controller
{
    public function __construct(
        private readonly AccountService $account,
        private readonly SessionDeviceService $sessions,
    ) {}

    public function show(Request $request): View
    {
        $portal = Str::before($request->route()->getName(), '.');
        $user = $request->user();

        return view("{$portal}.account.index", [
            'portalPrefix' => $portal,
            'accountUser' => $user,
            'activeSessions' => $this->sessions->activeSessions($user),
            'currentSessionId' => $request->session()->getId(),
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $this->account->updateName($request->user(), $data['name']);

        return back()->with('status', __('Name updated.'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        $this->account->changePassword($request->user(), $data['current_password'], $data['password']);

        // A password change is exactly when a compromised session should be
        // kicked out — otherwise an attacker who's already logged in keeps
        // their session even after the real owner "secures" the account.
        $this->sessions->revokeOtherSessions($request->user(), $request->session()->getId());

        return back()->with('status', __('Password updated.'));
    }

    public function requestEmailChange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:users,pending_email'],
        ]);
        $this->account->requestEmailChange($request->user(), $data['email']);

        return back()->with('status', __('Check the new address for a confirmation link — your email will not change until you click it.'));
    }

    public function cancelEmailChange(Request $request): RedirectResponse
    {
        $this->account->cancelEmailChange($request->user());

        return back()->with('status', __('Pending email change cancelled.'));
    }

    /** Signed route from the confirmation email — requires being logged in as the matching user. */
    public function confirmEmailChange(Request $request, int $user, string $token): RedirectResponse
    {
        if (! $request->hasValidSignature() || $request->user()->id !== $user) {
            abort(403);
        }

        $this->account->confirmEmailChange($request->user(), $token);

        return back()->with('status', __('Email address updated.'));
    }

    /**
     * Signed route from the "wasn't you?" notice mailed to the OLD address —
     * deliberately NOT behind 'auth', since the real owner may already be
     * locked out if the account is genuinely compromised. The signature
     * alone guards the user id/token from tampering; the token is then
     * checked against the live pending state so a stale or reused link
     * fails quietly instead of cancelling something that's already resolved.
     */
    public function cancelEmailChangeExternal(Request $request, int $user, string $token): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $target = User::findOrFail($user);
        $cancelled = $this->account->cancelEmailChangeWithToken($target, $token);

        return redirect()->route('login')->with(
            $cancelled ? 'status' : 'error',
            $cancelled
                ? __("The pending email change was cancelled. If this wasn't you, please change your password right away.")
                : __('This link is invalid or the change was already handled.'),
        );
    }

    public function showEnableTwoFactor(Request $request): View
    {
        $portal = Str::before($request->route()->getName(), '.');
        $secret = $request->session()->get('2fa_setup_secret') ?? $this->account->generateSecret();
        $request->session()->put('2fa_setup_secret', $secret);

        return view("{$portal}.account.two-factor-setup", [
            'portalPrefix' => $portal,
            'secret' => $secret,
            'qrUrl' => $this->account->qrCodeUrl($request->user(), $secret),
        ]);
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret) {
            return back()->withErrors(['code' => __('Your setup session expired — start again.')]);
        }

        $codes = $this->account->confirmTwoFactor($request->user(), $secret, $data['code']);
        $request->session()->forget('2fa_setup_secret');
        $request->session()->flash('recovery_codes', $codes);

        return back()->with('status', __('Two-factor authentication enabled. Save your recovery codes somewhere safe.'));
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string']]);
        $this->account->disableTwoFactor($request->user(), $data['current_password']);

        // Removing a security factor should also cut off any other session —
        // same reasoning as the password-change revoke above.
        $this->sessions->revokeOtherSessions($request->user(), $request->session()->getId());

        return back()->with('status', __('Two-factor authentication disabled.'));
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $codes = $this->account->regenerateRecoveryCodes($request->user());
        $request->session()->flash('recovery_codes', $codes);

        return back()->with('status', __('New recovery codes generated — your old ones no longer work.'));
    }

    public function revokeSession(Request $request, int $history): RedirectResponse
    {
        $this->sessions->revokeSession($request->user(), $history);

        return back()->with('status', __('Session signed out.'));
    }

    public function revokeOtherSessions(Request $request): RedirectResponse
    {
        $count = $this->sessions->revokeOtherSessions($request->user(), $request->session()->getId());

        return back()->with('status', __('Signed out :count other session(s).', ['count' => $count]));
    }
}
