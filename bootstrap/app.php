<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Middleware\CheckModuleEnabled;
use App\Http\Middleware\ResolveSchool;
use App\Http\Middleware\SetCurrentSchoolFromSession;
use App\Http\Middleware\SetLocale;
use App\Modules\Attendance\Console\AutoCloseStaffAttendance;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        AutoCloseStaffAttendance::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', ResolveSchool::class);

        // Locale resolution + DB-stored translations on every web request.
        $middleware->appendToGroup('web', SetLocale::class);

        // Gateways POST cross-site with no session CSRF token: SSLCommerz's
        // browser return and the Stripe/PayPal server-to-server webhooks.
        $middleware->validateCsrfTokens(except: [
            'portal/pay/sslcommerz/*',
            'payments/webhook/*',
        ]);

        // Guests hitting a protected area are sent to that area's branded login;
        // already-authenticated users hitting a login go to their role's portal.
        $middleware->redirectGuestsTo(
            fn (Request $request) => LoginController::loginUrlFor($request),
        );
        $middleware->redirectUsersTo(
            fn (Request $request) => LoginController::homeFor($request->user()),
        );

        $middleware->alias([
            'ability' => CheckForAnyAbility::class,
            'abilities' => CheckAbilities::class,
            'module.enabled' => CheckModuleEnabled::class,
            'school' => SetCurrentSchoolFromSession::class,
            // Spatie role check (distinct from Sanctum ability checks) — gates
            // role-restricted routes such as admin/accountant areas.
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
