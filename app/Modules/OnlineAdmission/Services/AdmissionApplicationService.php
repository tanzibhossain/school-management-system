<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Models\User;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
     * The public website admission form (Public\AdmissionController). Core
     * fields map to the columns the approval→enrolment flow needs; the rest go
     * into form_data so schools can add/hide fields. Duplicates are rejected
     * before anything — including the photo — is stored.
     *
     * @param  array<string, mixed>  $data  the form's validated input
     *
     * @throws ValidationException on a duplicate ('duplicate' key)
     */
    public function submitPublicForm(int $schoolId, array $data, ?UploadedFile $photo): AdmissionApplication
    {
        $guardianType = $data['guardian_type'];
        $guardianName = $data['guardian_name'] ?? ($guardianType === 'mother' ? $data['mother_name'] : $data['father_name']);
        $guardianPhone = $data['guardian_phone'] ?? $data['father_phone'];
        $guardianRelation = in_array($guardianType, ['father', 'mother'], true) ? $guardianType : 'other';

        if ($this->isDuplicate($schoolId, $data, $guardianPhone)) {
            throw ValidationException::withMessages([
                'duplicate' => 'An application already exists for this student or guardian (matching birth certificate, NID, or phone number).',
            ]);
        }

        $photoPath = $photo?->store('admissions', 'public');

        return $this->submit($schoolId, [
            'applicant_name' => trim($data['first_name'].' '.($data['last_name'] ?? '')),
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'blood_group' => $data['blood_group'] ?? null,
            'birth_certificate_no' => $data['birth_certificate_no'],
            'student_phone' => $data['student_phone'] ?? null,
            'father_nid' => $data['father_nid'],
            'guardian_nid' => $guardianType === 'mother' ? $data['mother_nid'] : $data['father_nid'],
            'desired_class_id' => $data['desired_class_id'],
            'desired_academic_year_id' => $data['desired_academic_year_id'],
            'guardian_name' => $guardianName,
            'guardian_phone' => $guardianPhone,
            'guardian_relation' => $guardianRelation,
            'notes' => $data['notes'] ?? null,
            'form_data' => [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'religion' => $data['religion'],
                'previous_school' => $data['previous_school'],
                'gpa' => $data['gpa'],
                'father_name' => $data['father_name'],
                'father_phone' => $data['father_phone'],
                'mother_name' => $data['mother_name'],
                'mother_nid' => $data['mother_nid'],
                'guardian_type' => $guardianType,
                'guardian_relationship' => $data['guardian_relationship'] ?? null,
                'present_address' => $data['present_address'],
                'permanent_address' => $data['permanent_address'] ?? null,
                'is_permanent_same' => (bool) ($data['is_permanent_same'] ?? false),
                'photo' => $photoPath,
            ],
        ]);
    }

    /**
     * An open (submitted/approved) application already matches this student or
     * guardian by birth certificate, father's NID, guardian phone, or student phone.
     *
     * @param  array<string, mixed>  $data
     */
    private function isDuplicate(int $schoolId, array $data, mixed $guardianPhone): bool
    {
        return AdmissionApplication::where('school_id', $schoolId)
            ->whereIn('status', ['submitted', 'approved'])
            ->where(function ($q) use ($data, $guardianPhone) {
                $q->where('birth_certificate_no', $data['birth_certificate_no'])
                    ->orWhere('father_nid', $data['father_nid'])
                    ->orWhere('guardian_phone', $guardianPhone);
                if (! empty($data['student_phone'])) {
                    $q->orWhere('student_phone', $data['student_phone']);
                }
            })
            ->exists();
    }

    /**
     * Approve + enrol in one action. $decision: {admission_number, section_id,
     * class_id?, academic_year_id?, roll_number?} — class/year default to what
     * the applicant requested but can be overridden if plans changed since.
     *
     * @param  array<string, mixed>  $decision
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
