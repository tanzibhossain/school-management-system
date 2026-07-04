<?php

namespace App\Modules\Platform\Services;

use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Called from the EXISTING StudentService::enrol() / StaffService::hire() (shared-
 * file edits, same pattern as Payroll's abilities fix touching Leave/Loan). Schools
 * with plan_id = null are legacy/grandfathered from before this module existed —
 * NEVER capped, by design (confirmed decision).
 */
class PlanLimitService
{
    public function assertCanAddStudent(int $schoolId): void
    {
        $school = School::with('plan')->find($schoolId);

        if (! $school || ! $school->plan || $school->plan->isUnlimitedStudents()) {
            return;
        }

        $current = Student::query()->where('school_id', $schoolId)->active()->count();

        if ($current >= $school->plan->max_students) {
            throw new UnprocessableEntityHttpException(
                "Student limit reached for the {$school->plan->name} plan ({$school->plan->max_students} students). Upgrade your plan to add more."
            );
        }
    }

    public function assertCanAddStaff(int $schoolId): void
    {
        $school = School::with('plan')->find($schoolId);

        if (! $school || ! $school->plan || $school->plan->isUnlimitedStaff()) {
            return;
        }

        $current = Staff::query()->where('school_id', $schoolId)->active()->count();

        if ($current >= $school->plan->max_staff) {
            throw new UnprocessableEntityHttpException(
                "Staff limit reached for the {$school->plan->name} plan ({$school->plan->max_staff} staff). Upgrade your plan to add more."
            );
        }
    }
}
