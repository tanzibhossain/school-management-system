<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Models\User;
use App\Modules\School\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

/**
 * Role-scoped authentication. Three branded entry points share one session guard;
 * after sign-in the user is routed to the portal that matches their role.
 */
class LoginController extends Controller
{
    /** Portal → allowed roles + dashboard route. Order = priority for redirect. */
    private const PORTALS = [
        'admin' => ['roles' => ['super_admin', 'admin'], 'dashboard' => 'admin.dashboard'],
        'staff' => ['roles' => ['teacher', 'accountant', 'librarian', 'receptionist'], 'dashboard' => 'staff.dashboard'],
        'family' => ['roles' => ['student', 'parent'], 'dashboard' => 'portal.dashboard'],
    ];

    public function showAdmin()
    {
        return $this->form('admin');
    }

    public function showStaff()
    {
        return $this->form('staff');
    }

    public function showFamily()
    {
        return $this->form('family');
    }

    private function form(string $portal)
    {
        return view('auth.login', ['portal' => $portal, 'school' => School::first()]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        if (! Auth::user()->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        $request->session()->regenerate();

        // Route to the portal that matches the user's role, regardless of which
        // branded form they used to sign in.
        return redirect()->intended(self::homeFor(Auth::user()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /** Resolve the correct dashboard URL for a user by their role. */
    public static function homeFor(?User $user): string
    {
        if ($user) {
            foreach (self::PORTALS as $portal) {
                if ($user->hasAnyRole($portal['roles']) && Route::has($portal['dashboard'])) {
                    return route($portal['dashboard']);
                }
            }
        }

        return route('login');
    }

    /** Which branded login a guest should be sent to, based on the URL they hit. */
    public static function loginUrlFor(Request $request): string
    {
        if ($request->is('admin', 'admin/*')) {
            return route('admin.login');
        }
        if ($request->is('staff', 'staff/*')) {
            return route('staff.login');
        }

        return route('login');
    }
}
