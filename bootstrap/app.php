<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Modules\Attendance\Console\AutoCloseStaffAttendance::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('api', \App\Http\Middleware\ResolveSchool::class);

        // Gateways POST cross-site with no session CSRF token: SSLCommerz's
        // browser return and the Stripe/PayPal server-to-server webhooks.
        $middleware->validateCsrfTokens(except: [
            'portal/pay/sslcommerz/*',
            'payments/webhook/*',
        ]);

        // Guests hitting a protected area are sent to that area's branded login;
        // already-authenticated users hitting a login go to their role's portal.
        $middleware->redirectGuestsTo(
            fn (Request $request) => \App\Http\Controllers\Admin\Auth\LoginController::loginUrlFor($request),
        );
        $middleware->redirectUsersTo(
            fn (Request $request) => \App\Http\Controllers\Admin\Auth\LoginController::homeFor($request->user()),
        );

        $middleware->alias([
            'ability'    => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
            'abilities'  => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'module.enabled' => \App\Http\Middleware\CheckModuleEnabled::class,
            'school' => \App\Http\Middleware\SetCurrentSchoolFromSession::class,
            // Spatie role check (distinct from Sanctum ability checks) — gates
            // role-restricted routes such as admin/accountant areas.
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn(Request $request) => $request->is('api/*'),
        );
    })->create();
