<?php

namespace Tests\Feature;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Route;
use Laravel\Horizon\Http\Middleware\Authenticate as HorizonAuthenticate;
use Tests\TestCase;

/**
 * Every POST/PUT/PATCH/DELETE route must require a login, except the ones
 * that are public on purpose (listed below with how each is protected
 * instead). 58 FormRequests return `true` from authorize() and rely entirely
 * on route middleware, so a write route that silently lost its `auth`
 * middleware would be an open endpoint — this test makes that a CI failure.
 *
 * Adding a new public write route? Add it to PUBLIC_WRITE_ROUTES with a note
 * on what protects it (rate limit, signature check, server-side verification).
 */
class WriteRoutesRequireAuthTest extends TestCase
{
    private const PUBLIC_WRITE_ROUTES = [
        // Sign-in flows — guest-only + throttle:login / throttle:two-factor.
        'login', 'admin/login', 'staff/login', 'two-factor-challenge', 'api/v2/auth/login',
        // Public forms — applicants/visitors have no account; throttle:10,1.
        'admission', 'contact', 'api/v2/admission-applications',
        // Payment gateways can't log in. Stripe/PayPal webhooks verify a
        // signature; SSLCommerz callbacks are re-verified against
        // SSLCommerz's own validation API (PaymentService::verifySslcommerz).
        'payments/webhook/stripe', 'payments/webhook/paypal',
        'api/v2/payments/sslcommerz/ipn', 'api/v2/payments/sslcommerz/success',
        'api/v2/payments/sslcommerz/fail', 'api/v2/payments/sslcommerz/cancel',
        'portal/pay/sslcommerz/{result}',
        // Laravel's own local-disk upload route (filesystems.local.serve):
        // rejects anything without a valid signed URL.
        'storage/{path}',
    ];

    public function test_every_write_route_requires_authentication_unless_deliberately_public(): void
    {
        $router = app('router');
        $offenders = [];

        foreach (Route::getRoutes() as $route) {
            if (! array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                continue;
            }
            if (in_array($route->uri(), self::PUBLIC_WRITE_ROUTES, true)) {
                continue;
            }

            $authenticated = collect($router->gatherRouteMiddleware($route))->contains(
                fn ($m) => is_string($m) && (str_starts_with($m, Authenticate::class) || str_starts_with($m, HorizonAuthenticate::class))
            );

            if (! $authenticated) {
                $offenders[] = implode('|', $route->methods()).' '.$route->uri();
            }
        }

        $this->assertSame([], $offenders, 'Write routes reachable without logging in: '.implode(', ', $offenders));
    }
}
