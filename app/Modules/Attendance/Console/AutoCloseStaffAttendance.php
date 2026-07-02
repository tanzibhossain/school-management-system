<?php

namespace App\Modules\Attendance\Console;

use App\Modules\Attendance\Services\StaffAttendanceService;
use App\Modules\School\Models\School;
use Illuminate\Console\Command;

/**
 * Auto clock-out forgotten staff punches, per school policy.
 * Scheduled every 30 minutes — each school is evaluated in its OWN timezone,
 * so one run safely serves schools in many countries.
 */
class AutoCloseStaffAttendance extends Command
{
    protected $signature = 'attendance:auto-close';

    protected $description = 'Auto clock-out open staff attendance records after each school\'s closing time';

    public function handle(StaffAttendanceService $service): int
    {
        $closed = 0;

        School::where('is_active', true)->each(function (School $school) use ($service, &$closed): void {
            $closed += $service->autoCloseForSchool($school);
        });

        $this->info("Auto-closed {$closed} staff attendance record(s).");

        return self::SUCCESS;
    }
}
