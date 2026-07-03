<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Models\User;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Student\Services\StudentService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * submit() creates the application; approve() calls the SAME
 * StudentService::enrol() the normal Student API and DataImport both use —
 * an applicant admitted through this module is enrolled under identical
 * business rules (ID generation is NOT part of that — admission_number is
 * administrator-assigned everywhere else in this codebase, so approve()
 * takes one here too, same as a manual enrolment would require) rather than
 * a second, divergent code path.
 */
class AdmissionApplicationService
{
    public function __construct(
        private readonly AdmissionReferenceGeneratorService $referenceGenerator,
        private readonly StudentService $studentService,
    ) {}

    /** @param array<string, mixed> $data */
    public function submit(int $schoolId, array $data): AdmissionApplication
    {
        return AdmissionApplication::create(array_merge($data, [
            'school_id' => $schoolId,
            'reference_number' => $this->referenceGenerator->generate($schoolId),
            'status' => 'submitted',
        ]));
    }

    /**
     * Approve + enrol in one action. $decision: {admission_number, section_id,
     * class_id?, academic_year_id?, roll_number?} — class/year default to what
     * the applicant requested but can be overridden if plans changed since.
     *
     * @param array<string, mixed> $decision
     */
    public function approve(AdmissionApplication $application, User $user, array $decision): AdmissionApplication
    {
        $this->guardUndecided($application);

        return DB::transaction(function () use ($application, $user, $decision): AdmissionApplication {
            $student = $this->studentService->enrol(
                $application->school_id,
                [
                    'admission_number' => $decision['admission_number'],
                    'name' => $application->applicant_name,
                    'gender' => $application->gender,
                    'dob' => $application->dob?->toDateString(),
                    'blood_group' => $application->blood_group,
                ],
                [
                    'academic_year_id' => $decision['academic_year_id'] ?? $application->desired_academic_year_id,
                    'class_id' => $decision['class_id'] ?? $application->desired_class_id,
                    'section_id' => $decision['section_id'],
                    'roll_number' => $decision['roll_number'] ?? null,
                ],
                [
                    [
                        'relation' => $application->guardian_relation,
                        'name' => $application->guardian_name,
                        'phone' => $application->guardian_phone,
                        'email' => $application->guardian_email,
                    ],
                ],
            );

            $application->update([
                'status' => 'approved',
                'decided_by' => $user->id,
                'decided_at' => now(),
                'created_student_id' => $student->id,
            ]);

            return $application->fresh(['student']);
        });
    }

    public function reject(AdmissionApplication $application, User $user, string $reason): AdmissionApplication
    {
        $this->guardUndecided($application);

        $application->update([
            'status' => 'rejected',
            'decision_reason' => $reason,
            'decided_by' => $user->id,
            'decided_at' => now(),
        ]);

        return $application->fresh();
    }

    /** Public lookup — reference alone is guessable, so guardian_phone must match too. */
    public function checkStatus(int $schoolId, string $referenceNumber, string $guardianPhone): ?AdmissionApplication
    {
        return AdmissionApplication::forSchool($schoolId)
            ->where('reference_number', $referenceNumber)
            ->where('guardian_phone', $guardianPhone)
            ->first();
    }

    private function guardUndecided(AdmissionApplication $application): void
    {
        if ($application->status !== 'submitted') {
            throw new UnprocessableEntityHttpException(
                "This application has already been {$application->status}."
            );
        }
    }
}
