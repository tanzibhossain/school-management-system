<?php

namespace App\Modules\Student\Services;

use App\Models\User;
use App\Modules\Academic\Models\Section;
use App\Modules\Student\Events\StudentEnrolled;
use App\Modules\Student\Events\StudentPromoted;
use App\Modules\Student\Events\StudentReAdmitted;
use App\Modules\Student\Events\StudentTransferred;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use App\Modules\Student\Models\StudentSibling;
use App\Modules\Student\Repositories\StudentRepository;
use App\Modules\Platform\Services\PlanLimitService;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class StudentService extends BaseService
{
    public function __construct(
        StudentRepository $repository,
        private readonly StudentIdGeneratorService $idGenerator,
        private readonly PlanLimitService $planLimit,
    ) {
        parent::__construct($repository);
    }

    /**
     * Enrol a new student: generate ID, create academic record, fire event.
     *
     * @param  array<string, mixed>  $studentData
     * @param  array<string, mixed>  $academicData  {academic_year_id, class_id, section_id, ...}
     * @param  array<string, mixed>  $guardianData
     */
    public function enrol(
        int $schoolId,
        array $studentData,
        array $academicData,
        array $guardianData = [],
    ): Student {
        return DB::transaction(function () use ($schoolId, $studentData, $academicData, $guardianData): Student {
            // Platform module (#23) — plan cap check (no-op for legacy/grandfathered
            // schools with plan_id = null).
            $this->planLimit->assertCanAddStudent($schoolId);

            // Capacity check
            $sectionId = $academicData['section_id'];
            $yearId    = $academicData['academic_year_id'];
            $section   = Section::findOrFail($sectionId);

            if ($section->capacity !== null) {
                /** @var StudentRepository $repo */
                $repo = $this->repository;
                $enrolled = $repo->countInSection($sectionId, $yearId);

                if ($enrolled >= $section->capacity) {
                    throw new UnprocessableEntityHttpException(
                        "Section is full (capacity: {$section->capacity}). Add student to waitlist."
                    );
                }
            }

            // Generate student ID
            $studentId = $this->idGenerator->generate($schoolId);

            $student = Student::create(array_merge($studentData, [
                'school_id'  => $schoolId,
                'student_id' => $studentId,
                'status'     => 'active',
            ]));

            // Create academic record
            StudentAcademic::create(array_merge($academicData, [
                'school_id'  => $schoolId,
                'student_id' => $student->id,
                'is_current' => true,
            ]));

            // Create guardians if provided
            foreach ($guardianData as $guardian) {
                StudentGuardian::create(array_merge($guardian, [
                    'school_id'  => $schoolId,
                    'student_id' => $student->id,
                ]));
            }

            event(new StudentEnrolled($student));

            return $student->load(['currentAcademic', 'guardians']);
        });
    }

    /**
     * Promote student to a new class/section in a new academic year.
     */
    public function promote(
        Student $student,
        int $toClassId,
        int $toSectionId,
        int $toYearId,
        ?int $toVersionId = null,
        ?int $toGroupId = null,
        ?int $toShiftId = null,
        ?string $rollNumber = null,
    ): Student {
        return DB::transaction(function () use (
            $student, $toClassId, $toSectionId, $toYearId,
            $toVersionId, $toGroupId, $toShiftId, $rollNumber
        ): Student {
            // Capacity check for target section
            $section = Section::findOrFail($toSectionId);
            if ($section->capacity !== null) {
                /** @var StudentRepository $repo */
                $repo = $this->repository;
                $enrolled = $repo->countInSection($toSectionId, $toYearId);
                if ($enrolled >= $section->capacity) {
                    throw new UnprocessableEntityHttpException(
                        "Target section is full (capacity: {$section->capacity})."
                    );
                }
            }

            // Mark previous academic record as not current
            StudentAcademic::where('student_id', $student->id)
                ->where('is_current', true)
                ->update(['is_current' => false, 'promoted_at' => now()]);

            // Create new academic record
            StudentAcademic::create([
                'school_id'       => $student->school_id,
                'student_id'      => $student->id,
                'academic_year_id' => $toYearId,
                'class_id'        => $toClassId,
                'section_id'      => $toSectionId,
                'version_id'      => $toVersionId,
                'group_id'        => $toGroupId,
                'shift_id'        => $toShiftId,
                'roll_number'     => $rollNumber,
                'is_current'      => true,
            ]);

            $this->repository->flush();
            event(new StudentPromoted($student->fresh(['currentAcademic'])));

            return $student->fresh(['currentAcademic.schoolClass', 'currentAcademic.section']);
        });
    }

    /**
     * Transfer a student out — marks status, fires event (TC generated separately).
     */
    public function transfer(Student $student, string $reason = 'transfer'): Student
    {
        DB::transaction(function () use ($student, $reason): void {
            // Deactivate student portal login
            if ($user = $student->guardians()->whereNotNull('user_id')->with('user')->first()?->user) {
                $user->tokens()->delete();
            }

            $student->update(['status' => 'transferred']);
        });

        $this->repository->flush();
        event(new StudentTransferred($student->fresh(), $reason));

        return $student->fresh();
    }

    /**
     * Re-admit a previously transferred/graduated student.
     *
     * @param  array<string, mixed>  $academicData
     */
    public function reAdmit(Student $student, array $academicData): Student
    {
        return DB::transaction(function () use ($student, $academicData): Student {
            $student->increment('re_admission_count');
            $student->update(['status' => 'active', 'is_trash' => false]);

            StudentAcademic::create(array_merge($academicData, [
                'school_id'  => $student->school_id,
                'student_id' => $student->id,
                'is_current' => true,
            ]));

            $this->repository->flush();
            event(new StudentReAdmitted($student->fresh()));

            return $student->fresh(['currentAcademic']);
        });
    }

    /**
     * Deactivate without transfer (e.g. expelled / admin inactive).
     */
    public function deactivate(Student $student, string $status = 'inactive'): Student
    {
        $student->update(['status' => $status]);
        $this->repository->flush();

        return $student->fresh();
    }

    /**
     * Link two students as siblings (bidirectional) and share guardian accounts.
     */
    public function linkSiblings(Student $a, Student $b): void
    {
        DB::transaction(function () use ($a, $b): void {
            // Insert both directions, ignore duplicates
            StudentSibling::firstOrCreate(['school_id' => $a->school_id, 'student_id' => $a->id, 'sibling_id' => $b->id]);
            StudentSibling::firstOrCreate(['school_id' => $a->school_id, 'student_id' => $b->id, 'sibling_id' => $a->id]);

            $this->shareGuardianAccounts($a, $b);
        });

        $this->repository->flush();
    }

    /**
     * Copy guardian user_id across siblings (matched by relation).
     */
    private function shareGuardianAccounts(Student $a, Student $b): void
    {
        $aGuardians = $a->guardians()->whereNotNull('user_id')->get()->keyBy('relation');
        $bGuardians = $b->guardians()->whereNotNull('user_id')->get()->keyBy('relation');

        // A has user_ids → push to B
        foreach ($aGuardians as $relation => $guardian) {
            $b->guardians()->where('relation', $relation)->whereNull('user_id')
                ->update(['user_id' => $guardian->user_id]);
        }

        // B has user_ids → push to A
        foreach ($bGuardians as $relation => $guardian) {
            $a->guardians()->where('relation', $relation)->whereNull('user_id')
                ->update(['user_id' => $guardian->user_id]);
        }
    }

    /**
     * Store a photo in MinIO and update the student record.
     */
    public function uploadPhoto(Student $student, UploadedFile $file): Student
    {
        $path = $file->store("students/{$student->school_id}/photos", 'minio');
        $student->update(['photo' => $path]);
        $this->repository->flush();

        return $student->fresh();
    }
}
