<?php

namespace App\Modules\Attendance\Services;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\Section;
use App\Modules\Attendance\Models\AttendanceSetting;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepository $repository,
        private readonly WorkingDayService $workingDays,
    ) {}

    /**
     * Bulk-upsert a class/section register for one date.
     * Resubmitting the register UPDATES existing rows — never duplicates, never errors.
     *
     * @param  array<int, array{student_id: int, status: string, note?: string|null}>  $entries
     * @return array{created: int, updated: int}
     */
    public function bulkUpsert(
        int $schoolId,
        int $classId,
        ?int $sectionId,
        string $date,
        array $entries,
        User $recorder,
    ): array {
        $day = CarbonImmutable::parse($date);

        $this->assertWorkingDay($schoolId, $day);
        $this->assertWithinEditWindow($schoolId, $day, $recorder);
        $this->assertCanRecord($recorder, $sectionId);

        $yearId = $this->currentAcademicYearId($schoolId);

        $existing = StudentAttendance::forSchool($schoolId)
            ->onDate($day->toDateString())
            ->whereIn('student_id', array_column($entries, 'student_id'))
            ->pluck('id', 'student_id');

        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $schoolId, $classId, $sectionId, $day, $entries, $recorder, $yearId, $existing, &$created, &$updated
        ): void {
            foreach ($entries as $entry) {
                $attributes = [
                    'school_id' => $schoolId,
                    'student_id' => $entry['student_id'],
                    'date' => $day->toDateString(),
                ];

                $values = [
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'academic_year_id' => $yearId,
                    'status' => $entry['status'],
                    'note' => $entry['note'] ?? null,
                ];

                if ($existing->has($entry['student_id'])) {
                    // Correction — keep original recorder, audit the editor
                    StudentAttendance::whereKey($existing[$entry['student_id']])
                        ->update($values + ['edited_by' => $recorder->id]);
                    $updated++;
                } else {
                    StudentAttendance::create($attributes + $values + ['recorded_by' => $recorder->id]);
                    $created++;
                }
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Attendance summary for one student.
     * Denominator = working days within the range, clamped to the student's
     * enrollment start (mid-year admissions are not penalised). Leave is excused:
     * excluded from absents; configurable whether it stays in the denominator.
     *
     * @return array<string, mixed>
     */
    public function studentSummary(int $schoolId, int $studentId, string $from, string $to): array
    {
        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);
        $settings = AttendanceSetting::forSchool($schoolId);

        $fromDay = CarbonImmutable::parse($from);
        $toDay = CarbonImmutable::parse($to);

        // Enrollment clamp — count from admission, not from before the student existed
        $enrolledFrom = CarbonImmutable::parse($student->created_at->toDateString());
        if ($enrolledFrom->greaterThan($fromDay)) {
            $fromDay = $enrolledFrom;
        }

        $counts = $this->repository->statusCounts($schoolId, $studentId, $fromDay->toDateString(), $toDay->toDateString());
        $workingDays = $this->workingDays->countWorkingDays($schoolId, $fromDay, $toDay);

        $present = (int) ($counts['present'] ?? 0);
        $late = (int) ($counts['late'] ?? 0);
        $halfDay = (int) ($counts['half_day'] ?? 0);
        $absent = (int) ($counts['absent'] ?? 0);
        $leave = (int) ($counts['leave'] ?? 0);

        $denominator = $settings->leave_counts_in_denominator
            ? $workingDays
            : max(0, $workingDays - $leave);

        $attended = $present + $late + ($halfDay * 0.5);
        $percentage = $denominator > 0 ? round(($attended / $denominator) * 100, 2) : 0.0;

        return [
            'student_id' => $studentId,
            'from' => $fromDay->toDateString(),
            'to' => $toDay->toDateString(),
            'working_days' => $workingDays,
            'present' => $present,
            'late' => $late,
            'half_day' => $halfDay,
            'absent' => $absent,
            'leave' => $leave,
            'percentage' => $percentage,
        ];
    }

    // ── Guards ───────────────────────────────────────────────────────────────

    private function assertWorkingDay(int $schoolId, CarbonImmutable $day): void
    {
        if (! $this->workingDays->isWorkingDay($schoolId, $day)) {
            throw ValidationException::withMessages([
                'date' => ["{$day->toDateString()} is not a working day (weekend or holiday)."],
            ]);
        }
    }

    /**
     * Corrections are allowed within edit_window_days; older dates need admin ability.
     */
    private function assertWithinEditWindow(int $schoolId, CarbonImmutable $day, User $user): void
    {
        if ($user->tokenCan('admin:*')) {
            return;
        }

        $settings = AttendanceSetting::forSchool($schoolId);
        $school = School::findOrFail($schoolId);
        $today = $this->workingDays->todayFor($school);

        if ($day->lessThan($today->subDays($settings->edit_window_days))) {
            throw new AuthorizationException(
                "Attendance older than {$settings->edit_window_days} days can only be changed by an admin."
            );
        }

        if ($day->greaterThan($today)) {
            throw ValidationException::withMessages([
                'date' => ['Attendance cannot be recorded for a future date.'],
            ]);
        }
    }

    /**
     * Admins can record any register; teachers only their own section
     * (sections.class_teacher_id — set per section by the Head Teacher).
     */
    private function assertCanRecord(User $user, ?int $sectionId): void
    {
        if ($user->tokenCan('admin:*')) {
            return;
        }

        $staff = Staff::where('user_id', $user->id)->first();

        $isClassTeacher = $staff !== null
            && $sectionId !== null
            && Section::whereKey($sectionId)->where('class_teacher_id', $staff->id)->exists();

        if (! $isClassTeacher) {
            throw new AuthorizationException('Only the section\'s class teacher (or an admin) may record this register.');
        }
    }

    private function currentAcademicYearId(int $schoolId): int
    {
        $yearId = AcademicYear::where('school_id', $schoolId)
            ->where('is_current', true)
            ->value('id');

        if ($yearId === null) {
            throw ValidationException::withMessages([
                'academic_year' => ['No current academic year is set for this school.'],
            ]);
        }

        return (int) $yearId;
    }
}
