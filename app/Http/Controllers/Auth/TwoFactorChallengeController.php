<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Models\User;
use App\Modules\User\Services\AccountService;
use App\Modules\User\Services\SessionDeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

/**
 * Second step of login for accounts with two-factor enabled. LoginController
 * verifies the password, then immediately logs the guard back out and stashes
 * the pending user id in session instead of completing Auth::login() — this
 * page is the only way that pending state turns into a real session.
 */
class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AccountService $account,
        private readonly SessionDeviceService $sessions,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');
        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['required', 'string']]);
        $user = User::findOrFail($userId);

        if (! $this->passesChallenge($user, $data['code'])) {
            throw ValidationException::withMessages(['code' => [__('That code is invalid or has expired.')]]);
        }

        $remember = (bool) $request->session()->get('2fa.remember', false);
        $request->session()->forget(['2fa.user_id', '2fa.remember']);

        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate();
        $this->sessions->recordLogin($user, $request);

        return redirect()->intended(LoginController::homeFor($user));
    }

    private function passesChallenge(User $user, string $code): bool
    {
        if ($this->account->verifyCode($user->two_factor_secret, $code)) {
            return true;
        }

        return $this->account->redeemRecoveryCode($user, $code);
    }
}
