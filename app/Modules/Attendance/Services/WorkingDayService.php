<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Models\Holiday;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Working-day calendar for a school: opening-hours weekend config + holidays.
 * A "closure"-type holiday added retroactively acts as a void day — the date
 * drops out of every attendance percentage calculation automatically.
 */
class WorkingDayService
{
    /** "Today" is always the school's local date — one server, many countries. */
    public function todayFor(School $school): CarbonImmutable
    {
        return CarbonImmutable::now($school->timezone ?? 'UTC')->startOfDay();
    }

    public function isWorkingDay(int $schoolId, Carbon|CarbonImmutable $date): bool
    {
        return $this->isOpenWeekday($schoolId, $date)
            && ! $this->isHoliday($schoolId, $date);
    }

    public function isHoliday(int $schoolId, Carbon|CarbonImmutable $date): bool
    {
        return Holiday::forSchool($schoolId)->whereDate('date', $date->toDateString())->exists();
    }

    /** Weekend config lives in school_opening_hours (is_open per day_of_week, 0 = Sunday). */
    public function isOpenWeekday(int $schoolId, Carbon|CarbonImmutable $date): bool
    {
        $hour = SchoolOpeningHour::where('school_id', $schoolId)
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        // No config row = assume open weekday (fail-open keeps un-configured schools usable)
        return $hour === null || (bool) $hour->is_open;
    }

    /** That day's closing time, for auto clock-out. Falls back to 17:00. */
    public function closingTimeFor(int $schoolId, Carbon|CarbonImmutable $date): string
    {
        $hour = SchoolOpeningHour::where('school_id', $schoolId)
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        return $hour?->close_time ? substr((string) $hour->close_time, 0, 5) : '17:00';
    }

    /** Count working days between two dates inclusive (weekends + holidays excluded). */
    public function countWorkingDays(int $schoolId, Carbon|CarbonImmutable $from, Carbon|CarbonImmutable $to): int
    {
        if ($from->greaterThan($to)) {
            return 0;
        }

        $holidays = Holiday::forSchool($schoolId)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $closedWeekdays = SchoolOpeningHour::where('school_id', $schoolId)
            ->where('is_open', false)
            ->pluck('day_of_week')
            ->all();

        $count  = 0;
        $cursor = CarbonImmutable::parse($from->toDateString());
        $end    = CarbonImmutable::parse($to->toDateString());

        while ($cursor->lessThanOrEqualTo($end)) {
            if (! in_array($cursor->dayOfWeek, $closedWeekdays, true)
                && ! in_array($cursor->toDateString(), $holidays, true)) {
                $count++;
            }
            $cursor = $cursor->addDay();
        }

        return $count;
    }
}
