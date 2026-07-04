<?php

use App\Modules\Platform\Http\Controllers\CheckoutController;
use App\Modules\Platform\Http\Controllers\DemoLoginController;
use App\Modules\Platform\Http\Controllers\PublicPlanController;
use App\Modules\Platform\Http\Controllers\SetPasswordController;
use App\Modules\Platform\Http\Controllers\StripeWebhookController;
use App\Modules\Platform\Http\Controllers\SuperAdmin\PlanController;
use App\Modules\Platform\Http\Controllers\SuperAdmin\SchoolController;
use App\Modules\Platform\Http\Controllers\TrialSignupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform Module API Routes  —  prefix: /api/v2/platform
|--------------------------------------------------------------------------
| ResolveSchool already bypasses /api/v2/platform/* entirely (see
| App\Http\Middleware\ResolveSchool) — nothing here is scoped to
| current_school_id, by design (platform-level, not tenant-level).
*/

// ── Public — no login, throttled (visitor has no account yet) ───────────────
Route::middleware(['throttle:20,1'])->prefix('v2/platform')->group(function (): void {
    Route::get('/plans', [PublicPlanController::class, 'index']);
    Route::get('/demo', [DemoLoginController::class, 'show']);
    Route::post('/signup/trial', [TrialSignupController::class, 'store']);
    Route::post('/signup/checkout', [CheckoutController::class, 'store']);
    Route::post('/set-password', [SetPasswordController::class, 'store'])->name('platform.set-password');
});

// ── Stripe webhook — public, no auth (signature verification IS the gate) ───
// Deliberately its own group: no throttle (Stripe retries aggressively on
// non-2xx, throttling it would just cause more retries) and no CSRF/Sanctum.
Route::post('/v2/platform/webhooks/stripe', [StripeWebhookController::class, 'handle']);

// ── Super Admin portal — real Spatie role check, not a Sanctum ability ──────
Route::middleware(['auth:sanctum', 'role:super_admin'])->prefix('v2/platform/admin')->group(function (): void {
    Route::get('/schools', [SchoolController::class, 'index']);
    Route::post('/schools', [SchoolController::class, 'store']);
    Route::get('/schools/{id}', [SchoolController::class, 'show'])->whereNumber('id');
    Route::patch('/schools/{id}/plan', [SchoolController::class, 'updatePlan'])->whereNumber('id');

    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/plans', [PlanController::class, 'store']);
    Route::put('/plans/{plan}', [PlanController::class, 'update'])->whereNumber('plan');
});
