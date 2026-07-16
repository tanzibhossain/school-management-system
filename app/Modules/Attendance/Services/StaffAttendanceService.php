<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Models\AttendanceSetting;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffAttendanceService
{
    public function __construct(
        private readonly WorkingDayService $workingDays,
    ) {}

    /**
     * Record a punch (RFID device or manual button).
     * First punch of the day = check_in, every later punch moves check_out —
     * so intermediate punches are harmless and the last one wins.
     * Dates are school-local, never UTC-derived.
     */
    public function punch(int $schoolId, string $rfidNumber, string $source = 'rfid'): StaffAttendance
    {
        $staff = Staff::where('school_id', $schoolId)
            ->where('rfid_number', $rfidNumber)
            ->first();

        if ($staff === null) {
            throw ValidationException::withMessages([
                'rfid_number' => ['No staff member matches this RFID card.'],
            ]);
        }

        return $this->punchStaff($schoolId, $staff, $source);
    }

    /**
     * Punch for a known staff member (e.g. a self-service clock in/out from the
     * staff portal). Same first-punch-is-check-in / last-punch-is-check-out rule.
     */
    public function punchStaff(int $schoolId, Staff $staff, string $source = 'manual'): StaffAttendance
    {
        $school = School::findOrFail($schoolId);
        $now    = CarbonImmutable::now($school->timezone ?? 'UTC');
        $today  = $now->toDateString();

        return DB::transaction(function () use ($schoolId, $staff, $now, $today, $source): StaffAttendance {
            $record = StaffAttendance::forSchool($schoolId)
                ->where('staff_id', $staff->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                return StaffAttendance::create([
                    'school_id' => $schoolId,
                    'staff_id'  => $staff->id,
                    'date'      => $today,
                    'check_in'  => $now,
                    'source'    => $source,
                ]);
            }

            // Later punch — becomes (or moves) the clock-out; a real punch replaces an auto-close
            $record->update([
                'check_out'      => $now,
                'is_auto_closed' => false,
                'source'         => $source,
            ]);

            return $record->fresh();
        });
    }

    /**
     * Manual entry/correction by admin. A clock-out with no clock-in is stored
     * but flagged incomplete — we never invent a check_in.
     *
     * @param  array{staff_id: int, date: string, check_in?: string|null, check_out?: string|null, note?: string|null}  $data
     */
    public function recordManual(int $schoolId, array $data): StaffAttendance
    {
        $isIncomplete = empty($data['check_in']) && ! empty($data['check_out']);

        return StaffAttendance::updateOrCreate(
            [
                'school_id' => $schoolId,
                'staff_id'  => $data['staff_id'],
                'date'      => $data['date'],
            ],
            [
                'check_in'       => $data['check_in'] ?? null,
                'check_out'      => $data['check_out'] ?? null,
                'note'           => $data['note'] ?? null,
                'source'         => 'manual',
                'is_incomplete'  => $isIncomplete,
                'is_auto_closed' => false,
            ],
        );
    }

    /**
     * Auto clock-out forgotten open records for ONE school, per its policy.
     * check_out is that day's closing time from school_opening_hours — NEVER the
     * job run time. Auto-closed hours must not feed payroll/overtime unapproved.
     *
     * @return int number of records closed
     */
    public function autoCloseForSchool(School $school): int
    {
        $settings = AttendanceSetting::forSchool($school->id);

        if ($settings->auto_close_policy === 'off') {
            return 0;
        }

        $now   = CarbonImmutable::now($school->timezone ?? 'UTC');
        $today = $now->toDateString();

        $open = StaffAttendance::forSchool($school->id)
            ->open()
            ->where(function ($query) use ($today, $now, $school): void {
                // Any past day, or today once the school has closed
                $query->whereDate('date', '<', $today);

                $closing = $this->workingDays->closingTimeFor($school->id, $now);
                if ($now->format('H:i') >= $closing) {
                    $query->orWhereDate('date', $today);
                }
            })
            ->get();

        foreach ($open as $record) {
            $record->update([
                'check_out'      => $this->autoCloseTime($school, $settings, $record),
                'is_auto_closed' => true,
            ]);
        }

        return $open->count();
    }

    /** Day register for admin views. */
    public function register(int $schoolId, string $date): Collection
    {
        return StaffAttendance::forSchool($schoolId)
            ->whereDate('date', $date)
            ->with('staff:id,name,employee_id')
            ->orderBy('staff_id')
            ->get();
    }

    private function autoCloseTime(School $school, AttendanceSetting $settings, StaffAttendance $record): CarbonImmutable
    {
        $tz  = $school->timezone ?? 'UTC';
        $day = CarbonImmutable::parse($record->date->toDateString(), $tz);

        if ($settings->auto_close_policy === 'max_shift' && $record->check_in !== null) {
            return CarbonImmutable::parse($record->check_in, $tz)->addHours($settings->max_shift_hours);
        }

        // Default policy: that day's closing time from school_opening_hours
        return $day->setTimeFromTimeString($this->workingDays->closingTimeFor($school->id, $day));
    }
}
