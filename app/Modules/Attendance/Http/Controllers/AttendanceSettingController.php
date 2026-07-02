<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Modules\Attendance\Http\Requests\UpdateAttendanceSettingsRequest;
use App\Modules\Attendance\Http\Resources\AttendanceSettingResource;
use App\Modules\Attendance\Models\AttendanceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AttendanceSettingController extends Controller
{
    /**
     * GET /v2/attendance/settings
     * Force 200 — forSchool() lazily creates the row, and a fresh model
     * would otherwise make this GET return 201.
     */
    public function show(): JsonResponse
    {
        return (new AttendanceSettingResource(
            AttendanceSetting::forSchool(app('current_school_id'))
        ))->response()->setStatusCode(200);
    }

    /** PUT /v2/attendance/settings */
    public function update(UpdateAttendanceSettingsRequest $request): JsonResponse
    {
        $settings = AttendanceSetting::forSchool(app('current_school_id'));
        $settings->update($request->validated());

        return (new AttendanceSettingResource($settings->fresh()))
            ->response()->setStatusCode(200);
    }
}
