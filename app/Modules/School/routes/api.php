<?php

use App\Modules\School\Http\Controllers\SchoolController;
use Illuminate\Support\Facades\Route;

// Public — no auth
Route::get('/v2/public/school', [SchoolController::class, 'publicProfile']);

// Protected — Head Teacher / Admin
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v2/school', [SchoolController::class, 'show']);
    Route::put('/v2/school', [SchoolController::class, 'update']);
    Route::post('/v2/school/phones/sync', [SchoolController::class, 'syncPhones']);
    Route::put('/v2/school/hours/{day}', [SchoolController::class, 'updateHour'])
        ->whereNumber('day');
});
