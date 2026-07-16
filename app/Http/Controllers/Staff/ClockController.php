<?php

namespace App\Http\Controllers\Staff;

use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Attendance\Services\StaffAttendanceService;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Staff self-service clock in / out. First punch of the day is the check-in,
 * each later punch moves the check-out (mirrors the RFID device behaviour).
 */
class ClockController extends Controller
{
    public function __construct(private readonly StaffAttendanceService $attendance) {}

    public function index(): View
    {
        [$sid, $staff] = $this->context();

        $today = $this->today($sid);
        $todayRecord = $staff
            ? StaffAttendance::forSchool($sid)->where('staff_id', $staff->id)->whereDate('date', $today)->first()
            : null;

        $history = $staff
            ? StaffAttendance::forSchool($sid)->where('staff_id', $staff->id)->orderByDesc('date')->limit(30)->get()
            : collect();

        return view('staff.clock', compact('staff', 'todayRecord', 'history', 'today'));
    }

    public function punch(): RedirectResponse
    {
        [$sid, $staff] = $this->context();
        abort_unless($staff, 403, 'No staff record is linked to your account.');

        $this->attendance->punchStaff($sid, $staff, 'manual');

        return back()->with('status', 'Attendance recorded.');
    }

    private function today(int $sid): string
    {
        $tz = optional(School::find($sid))->timezone ?? 'UTC';

        return CarbonImmutable::now($tz)->toDateString();
    }

    /** @return array{0:int,1:?Staff} */
    private function context(): array
    {
        $sid = app('current_school_id');

        return [$sid, Staff::where('school_id', $sid)->where('user_id', auth()->id())->first()];
    }
}
