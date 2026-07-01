<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/v2/health', function () {
    return response()->json([
        'status' => 'ok',
        'laravel' => app()->version(),
        'env' => app()->environment(),
        'db' => DB::connection()->getPdo() ? 'connected' : 'error',
        'redis' => Cache::store('redis')->set('ping', 'ok', 10) ? 'connected' : 'error',
    ]);
});

