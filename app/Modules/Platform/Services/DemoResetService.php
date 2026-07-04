<?php

namespace App\Modules\Platform\Services;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\Section;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Services\StaffService;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Services\StudentService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Wipes and reseeds the ONE shared `is_demo=true` school on a schedule (see
 * Platform\Console\ResetDemoSchool, run twice daily as the closest practical
 * approximation of "every 14 hours" — cron can't express that interval exactly).
 *
 * Scope note: only Student/Staff rows are wiped and reseeded here (relying on each
 * module's existing FK cascadeOnDelete to clean up dependent attendance/marks/
 * payments/etc. automatically). The demo school's own academic structure
 * (AcademicYear/SchoolClass/Section/Designation) is assumed to already exist —
 * provisioning THAT structure the first time is a separate seeder concern, not
 * rebuilt on every reset. A full synthetic dataset across all 22+ modules was
 * judged out of proportion to this pass — flagged as a known gap.
 */
class DemoResetService
{
    public function __construct(
        private readonly StudentService $studentService,
        private readonly StaffService $staffService,
    ) {}

    public function reset(): void
    {
        $school = School::where('is_demo', true)->first();

        if (! $school) {
            Log::warning('Platform: demo reset skipped — no is_demo school exists yet.');

            return;
        }

        Student::where('school_id', $school->id)->each(fn (Student $s) => $s->delete());
        Staff::where('school_id', $school->id)->each(fn (Staff $s) => $s->delete());

        $this->reseed($school);
    }

    private function reseed(School $school): void
    {
        $year = AcademicYear::where('school_id', $school->id)->latest('id')->first();
        $section = Section::where('school_id', $school->id)->first();

        if (! $year || ! $section) {
            Log::warning("Platform: demo reset for school {$school->id} skipped reseed — no academic year/section configured yet.");

            return;
        }

        $demoStudents = [
            ['name' => 'Demo Student One', 'gender' => 'male'],
            ['name' => 'Demo Student Two', 'gender' => 'female'],
            ['name' => 'Demo Student Three', 'gender' => 'male'],
        ];

        foreach ($demoStudents as $i => $data) {
            try {
                $this->studentService->enrol(
                    $school->id,
                    [
                        'admission_number' => 'DEMO-' . ($i + 1),
                        'name' => $data['name'],
                        'gender' => $data['gender'],
                        'status' => 'active',
                    ],
                    [
                        'academic_year_id' => $year->id,
                        'class_id' => $section->class_id,
                        'section_id' => $section->id,
                    ],
                );
            } catch (Throwable $e) {
                // Swallow — a partial reseed (e.g. one row hitting a stale unique
                // constraint) should never fail the whole scheduled job.
                Log::warning("Platform: demo reset failed to reseed student {$i}: {$e->getMessage()}");
            }
        }
    }
}
