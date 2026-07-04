<?php

use App\Modules\Website\Http\Controllers\MenuController;
use App\Modules\Website\Http\Controllers\PageController;
use App\Modules\Website\Http\Controllers\PageTemplateController;
use App\Modules\Website\Http\Controllers\PublicPortalController;
use App\Modules\Website\Http\Controllers\SiteLayoutController;
use App\Modules\Website\Http\Controllers\SiteSettingController;
use App\Modules\Website\Http\Controllers\WebsiteMediaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Website Module API Routes
|--------------------------------------------------------------------------
*/

// ── Public — no login, for the (not-yet-built) Next.js public site ──────────
Route::middleware(['throttle:60,1'])->prefix('public')->group(function (): void {
    Route::get('/site-chrome', [PublicPortalController::class, 'siteChrome']);
    Route::get('/redirect/{slug}', [PublicPortalController::class, 'redirect']);
    Route::get('/staff', [PublicPortalController::class, 'staff']);
    Route::get('/notices', [PublicPortalController::class, 'notices']);
    Route::get('/routine/{classId}', [PublicPortalController::class, 'routine'])->whereNumber('classId');
    Route::get('/stats', [PublicPortalController::class, 'stats']);
    Route::post('/results/check', [PublicPortalController::class, 'checkResult']);
    // Registered last — {slug} is a catch-all within this prefix.
    Route::get('/pages/{slug}', [PublicPortalController::class, 'page']);
});

// ── Admin — admin:* only ─────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/website')->group(function (): void {
    // Pages
    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::get('/pages/{id}', [PageController::class, 'show'])->whereNumber('id');
    Route::put('/pages/{id}', [PageController::class, 'update'])->whereNumber('id');
    Route::delete('/pages/{id}', [PageController::class, 'destroy'])->whereNumber('id');
    Route::post('/pages/{id}/layout', [PageController::class, 'saveLayout'])->whereNumber('id');
    Route::post('/pages/{id}/publish', [PageController::class, 'publish'])->whereNumber('id');
    Route::post('/pages/{id}/duplicate', [PageController::class, 'duplicate'])->whereNumber('id');
    Route::get('/pages/{id}/revisions', [PageController::class, 'revisions'])->whereNumber('id');
    Route::post('/pages/{id}/restore/{lid}', [PageController::class, 'restore'])->whereNumber('id')->whereNumber('lid');
    Route::post('/pages/{id}/set-homepage', [PageController::class, 'setHomepage'])->whereNumber('id');

    // Menus
    Route::get('/menus', [MenuController::class, 'index']);
    Route::post('/menus', [MenuController::class, 'store']);
    Route::get('/menus/{id}', [MenuController::class, 'show'])->whereNumber('id');
    Route::put('/menus/{id}', [MenuController::class, 'update'])->whereNumber('id');
    Route::delete('/menus/{id}', [MenuController::class, 'destroy'])->whereNumber('id');
    Route::put('/menus/{id}/items', [MenuController::class, 'replaceItems'])->whereNumber('id');

    // Site layouts (header/footer)
    Route::get('/site-layouts/{type}', [SiteLayoutController::class, 'show']);
    Route::put('/site-layouts/{type}', [SiteLayoutController::class, 'update']);
    Route::post('/site-layouts/{type}/publish', [SiteLayoutController::class, 'publish']);

    // Site settings (singleton per school)
    Route::get('/site-settings', [SiteSettingController::class, 'show']);
    Route::put('/site-settings', [SiteSettingController::class, 'update']);

    // Page templates
    Route::get('/page-templates', [PageTemplateController::class, 'index']);
    Route::post('/page-templates', [PageTemplateController::class, 'store']);

    // Media library
    Route::get('/media', [WebsiteMediaController::class, 'index']);
    Route::post('/media', [WebsiteMediaController::class, 'store']);
    Route::delete('/media/{id}', [WebsiteMediaController::class, 'destroy'])->whereNumber('id');
});
