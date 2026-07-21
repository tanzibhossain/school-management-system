<?php

use App\Modules\Attendance\Http\Controllers\AttendanceSettingController;
use App\Modules\Attendance\Http\Controllers\HolidayController;
use App\Modules\Attendance\Http\Controllers\StaffAttendanceController;
use App\Modules\Attendance\Http\Controllers\StudentAttendanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Attendance Module API Routes  —  prefix: /api/v2
|--------------------------------------------------------------------------
*/

// ── Teachers + admins: student registers ─────────────────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*,teacher:*'])->prefix('v2/attendance')->group(function (): void {
    Route::post('/students/bulk', [StudentAttendanceController::class, 'bulkStore']);
    Route::get('/students/register', [StudentAttendanceController::class, 'register']);
    Route::get('/students/{studentId}/summary', [StudentAttendanceController::class, 'summary'])
        ->whereNumber('studentId');
});

// ── Admin only: staff attendance, settings, holidays ─────────────────────
Route::middleware(['auth:sanctum', 'ability:admin:*'])->prefix('v2/attendance')->group(function (): void {
    Route::post('/staff/punch', [StaffAttendanceController::class, 'punch']);
    Route::post('/staff/manual', [StaffAttendanceController::class, 'storeManual']);
    Route::get('/staff', [StaffAttendanceController::class, 'index']);

    Route::get('/settings', [AttendanceSettingController::class, 'show']);
    Route::put('/settings', [AttendanceSettingController::class, 'update']);

    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])->whereNumber('id');
});
