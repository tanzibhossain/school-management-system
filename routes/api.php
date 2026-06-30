<?php

use IlluminateSupportFacadesRoute;
use IlluminateSupportFacadesDB;
use IlluminateSupportFacadesCache;

Route::get('/v2/health', function () {
    return response()->json([
        'status'  => 'ok',
        'laravel' => app()->version(),
        'env'     => app()->environment(),
        'db'      => DB::connection()->getPdo() ? 'connected' : 'error',
        'redis'   => Cache::store('redis')->set('ping', 'ok', 10) ? 'connected' : 'error',
    ]);
});

