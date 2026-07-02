<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Modules\Attendance\Http\Requests\ManualStaffAttendanceRequest;
use App\Modules\Attendance\Http\Requests\StaffPunchRequest;
use App\Modules\Attendance\Http\Resources\StaffAttendanceResource;
use App\Modules\Attendance\Services\StaffAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StaffAttendanceController extends Controller
{
    public function __construct(
        private readonly StaffAttendanceService $service,
    ) {}

    /** POST /v2/attendance/staff/punch — RFID device or manual punch. */
    public function punch(StaffPunchRequest $request): StaffAttendanceResource
    {
        $record = $this->service->punch(
            app('current_school_id'),
            $request->validated('rfid_number'),
        );

        return new StaffAttendanceResource($record);
    }

    /** POST /v2/attendance/staff/manual — admin entry/correction. */
    public function storeManual(ManualStaffAttendanceRequest $request): StaffAttendanceResource
    {
        $record = $this->service->recordManual(
            app('current_school_id'),
            $request->validated(),
        );

        return new StaffAttendanceResource($record);
    }

    /** GET /v2/attendance/staff?date= — day register. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['date' => ['required', 'date_format:Y-m-d']]);

        return StaffAttendanceResource::collection(
            $this->service->register(app('current_school_id'), $request->query('date'))
        );
    }
}
