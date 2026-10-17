<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Models\User;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\OnlineAdmission\Support\AdmissionFormSchema;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAddress;
use App\Modules\Student\Models\StudentDocument;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

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
    /**
     * Applicant uploads (photo, custom file fields) go on the private local
     * disk — never web-reachable; admins open them through the role-gated
     * admin.admissions.file route.
     */
    public const FILE_DISK = 'local';

    /** Where photos were stored before uploads became private. */
    private const LEGACY_PHOTO_DISK = 'public';

    /** Where the Student module keeps student photos and documents. */
    private const STUDENT_DISK = 'minio';

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
     * before anything — including the photo — is stored. Answers to the
     * school's custom fields go into form_data.custom, each with the label it
     * had when submitted, so the application still reads right if the field
     * is renamed or removed later.
     *
     * @param  array<string, mixed>  $data  the form's validated input
     *
     * @throws ValidationException on a duplicate ('duplicate' key)
     */
    public function submitPublicForm(int $schoolId, array $data, ?UploadedFile $photo, ?AdmissionFormSchema $schema = null): AdmissionApplication
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

        $photoPath = $photo?->store("admissions/{$schoolId}", self::FILE_DISK) ?: null;
        $custom = $this->customAnswers($schoolId, $schema ?? new AdmissionFormSchema, $data['custom'] ?? []);

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
                'custom' => $custom,
            ],
        ]);
    }

    /**
     * One entry per custom field on the form, in form order; uploads are
     * stored and replaced by their path.
     *
     * @return array<string, array{label: string, type: string, value: string|bool|null}>
     */
    private function customAnswers(int $schoolId, AdmissionFormSchema $schema, mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $answers = [];
        foreach ($schema->customFields() as $key => $field) {
            $raw = $input[$key] ?? null;
            $value = match ($field['type']) {
                'file' => $raw instanceof UploadedFile ? ($raw->store("admissions/{$schoolId}", self::FILE_DISK) ?: null) : null,
                'checkbox' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                default => is_scalar($raw) && trim((string) $raw) !== '' ? trim((string) $raw) : null,
            };
            $answers[$key] = ['label' => $field['label'], 'type' => $field['type'], 'value' => $value];
        }

        return $answers;
    }

    /**
     * Where an applicant's uploaded file lives — the photo ('photo') or a
     * custom file field (its key). Only paths recorded on this application are
     * ever returned; the caller never supplies a path. Null when there is no
     * such upload or it's missing from storage.
     *
     * @return array{disk: string, path: string}|null
     */
    public function storedFile(AdmissionApplication $application, string $key): ?array
    {
        $formData = $this->formData($application);
        if ($key === 'photo') {
            $path = $formData['photo'] ?? null;
            $disks = [self::FILE_DISK, self::LEGACY_PHOTO_DISK];
        } else {
            $field = $formData['custom'][$key] ?? null;
            $path = is_array($field) && ($field['type'] ?? null) === 'file' ? ($field['value'] ?? null) : null;
            $disks = [self::FILE_DISK];
        }

        if (! is_string($path) || $path === '') {
            return null;
        }
        foreach ($disks as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return ['disk' => $disk, 'path' => $path];
            }
        }

        return null;
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
     * Everything the student record has a place for is carried over: religion,
     * photo, guardians (primary + father/mother), addresses, and custom file
     * uploads as documents. The rest (birth certificate no., NIDs, previous
     * school, GPA, custom text answers) stays on the application, which links
     * to the student. Uploads are copied to student storage first and removed
     * again if the approval fails, so a failed approval leaves nothing behind.
     *
     * @param  array<string, mixed>  $decision
     */
    public function approve(AdmissionApplication $application, User $user, array $decision): AdmissionApplication
    {
        $this->guardUndecided($application);
        $copied = $this->copyUploadsToStudentStorage($application);

        try {
            return DB::transaction(function () use ($application, $user, $decision, $copied): AdmissionApplication {
                $student = $this->studentService->enrol(
                    $application->school_id,
                    [
                        'admission_number' => $decision['admission_number'],
                        'name' => $application->applicant_name,
                        'gender' => $application->gender,
                        'dob' => $application->dob?->toDateString(),
                        'blood_group' => $application->blood_group,
                        'religion' => $this->formText($application, 'religion'),
                        'photo' => $copied['photo'],
                    ],
                    [
                        'academic_year_id' => $decision['academic_year_id'] ?? $application->desired_academic_year_id,
                        'class_id' => $decision['class_id'] ?? $application->desired_class_id,
                        'section_id' => $decision['section_id'],
                        'roll_number' => $decision['roll_number'] ?? null,
                    ],
                    $this->guardiansFor($application),
                );

                $this->addAddresses($student, $application);
                foreach ($copied['documents'] as $document) {
                    StudentDocument::create([
                        'school_id' => $student->school_id,
                        'student_id' => $student->id,
                        'document_type' => 'other',
                        'file_path' => $document['path'],
                        'original_name' => $document['name'],
                        'uploaded_by' => $user->id,
                    ]);
                }

                $application->update([
                    'status' => 'approved',
                    'decided_by' => $user->id,
                    'decided_at' => now(),
                    'created_student_id' => $student->id,
                ]);

                return $application->fresh(['student']);
            });
        } catch (Throwable $e) {
            $paths = array_column($copied['documents'], 'path');
            if ($copied['photo'] !== null) {
                $paths[] = $copied['photo'];
            }
            Storage::disk(self::STUDENT_DISK)->delete($paths);

            throw $e;
        }
    }

    /**
     * The primary guardian from the application, plus the father and mother
     * from the form when neither is already that primary guardian.
     *
     * @return list<array<string, mixed>>
     */
    private function guardiansFor(AdmissionApplication $application): array
    {
        $guardians = [[
            'relation' => $application->guardian_relation,
            'name' => $application->guardian_name,
            'phone' => self::guardianPhone($application->guardian_phone),
            'email' => $application->guardian_email,
            'is_primary' => true,
        ]];

        $parents = [
            'father' => [$this->formText($application, 'father_name'), $this->formText($application, 'father_phone')],
            'mother' => [$this->formText($application, 'mother_name'), null],
        ];
        foreach ($parents as $relation => [$name, $phone]) {
            if ($name !== null && $application->guardian_relation !== $relation) {
                $guardians[] = ['relation' => $relation, 'name' => $name, 'phone' => self::guardianPhone($phone), 'is_primary' => false];
            }
        }

        return $guardians;
    }

    /**
     * student_guardians.phone is 20 characters. The form now enforces that, but
     * applications submitted before it allowed 30 — never fail an approval on it.
     */
    private static function guardianPhone(?string $phone): ?string
    {
        return $phone === null ? null : mb_substr($phone, 0, 20);
    }

    /** Present + permanent address from the form ("same as present" copies it). */
    private function addAddresses(Student $student, AdmissionApplication $application): void
    {
        $present = $this->formText($application, 'present_address');
        $permanent = ! empty($this->formData($application)['is_permanent_same'])
            ? $present
            : $this->formText($application, 'permanent_address');

        foreach (['present' => $present, 'permanent' => $permanent] as $type => $address) {
            if ($address !== null) {
                StudentAddress::create(['student_id' => $student->id, 'type' => $type, 'address' => $address]);
            }
        }
    }

    /**
     * Copy the applicant's photo and custom file uploads to student storage.
     * The originals stay with the application.
     *
     * @return array{photo: ?string, documents: list<array{path: string, name: string}>}
     */
    private function copyUploadsToStudentStorage(AdmissionApplication $application): array
    {
        $dir = "students/{$application->school_id}";
        $photo = $this->storedFile($application, 'photo');
        $copied = ['photo' => $photo ? $this->copyToStudentStorage($photo, "{$dir}/photos") : null, 'documents' => []];

        $custom = $this->formData($application)['custom'] ?? [];
        foreach (is_array($custom) ? $custom : [] as $key => $field) {
            if (! is_string($key)) {
                continue;
            }
            $file = $this->storedFile($application, $key);
            $path = $file ? $this->copyToStudentStorage($file, "{$dir}/documents") : null;
            if ($path !== null) {
                $label = is_array($field) && is_string($field['label'] ?? null) ? $field['label'] : $key;
                $copied['documents'][] = ['path' => $path, 'name' => mb_substr($label, 0, 240).'.'.pathinfo($path, PATHINFO_EXTENSION)];
            }
        }

        return $copied;
    }

    /** @param  array{disk: string, path: string}  $file */
    private function copyToStudentStorage(array $file, string $dir): ?string
    {
        $contents = Storage::disk($file['disk'])->get($file['path']);
        $target = $dir.'/'.basename($file['path']);

        return $contents !== null && Storage::disk(self::STUDENT_DISK)->put($target, $contents) ? $target : null;
    }

    /** @return array<array-key, mixed> */
    private function formData(AdmissionApplication $application): array
    {
        return is_array($application->form_data) ? $application->form_data : [];
    }

    /** A trimmed, non-empty string from form_data, else null. */
    private function formText(AdmissionApplication $application, string $key): ?string
    {
        $value = $this->formData($application)[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
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
