<?php

namespace App\Modules\DataImport\Services;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\DataImport\Exceptions\RowImportException;
use App\Modules\DataImport\Services\Concerns\ParsesExcelDates;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Services\StudentService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Resolves one spreadsheet row (admission_number, name, gender, dob,
 * blood_group, class_name, section_name, academic_year, roll_number,
 * guardian_name, guardian_phone, guardian_relation) into IDs, validates it,
 * then calls the SAME StudentService::enrol() the normal Student API uses —
 * so an imported student is created under identical business rules (ID
 * generation, capacity checks) rather than a second, divergent code path.
 */
class StudentImportRowService
{
    use ParsesExcelDates;

    private const GENDERS = ['male', 'female', 'other'];

    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private const GUARDIAN_RELATIONS = ['father', 'mother', 'local_guardian', 'other'];

    public function __construct(private readonly StudentService $studentService) {}

    /**
     * @param  array<string, mixed>  $row
     *
     * @throws RowImportException
     */
    public function import(int $schoolId, array $row): void
    {
        $messages = [];

        $admissionNumber = trim((string) ($row['admission_number'] ?? ''));
        $name = trim((string) ($row['name'] ?? ''));
        $gender = strtolower(trim((string) ($row['gender'] ?? '')));

        if ($admissionNumber === '') {
            $messages[] = 'admission_number is required.';
        }
        if ($name === '') {
            $messages[] = 'name is required.';
        }
        if (! in_array($gender, self::GENDERS, true)) {
            $messages[] = 'gender must be one of: '.implode(', ', self::GENDERS).'.';
        }

        $bloodGroupRaw = trim((string) ($row['blood_group'] ?? ''));
        $bloodGroup = $bloodGroupRaw !== '' ? $bloodGroupRaw : null;
        if ($bloodGroup !== null && ! in_array($bloodGroup, self::BLOOD_GROUPS, true)) {
            $messages[] = "blood_group '{$bloodGroup}' is not valid.";
        }

        $dobRaw = $row['dob'] ?? null;
        $dob = $this->parseDate($dobRaw);
        if ($dobRaw !== null && trim((string) $dobRaw) !== '' && $dob === null) {
            $messages[] = 'dob could not be parsed as a date.';
        }

        // Class / section resolution — text -> ID, scoped to school_id (no
        // composite lookup exists on the Academic models, so this matches
        // by exact name, same as any other cross-module reference here).
        $className = trim((string) ($row['class_name'] ?? ''));
        $class = null;
        if ($className === '') {
            $messages[] = 'class_name is required.';
        } else {
            $class = SchoolClass::where('school_id', $schoolId)->where('name', $className)->first();
            if (! $class) {
                $messages[] = "Class '{$className}' was not found.";
            }
        }

        $sectionName = trim((string) ($row['section_name'] ?? ''));
        $section = null;
        if ($sectionName === '') {
            $messages[] = 'section_name is required.';
        } elseif ($class) {
            $section = Section::where('school_id', $schoolId)->where('class_id', $class->id)->where('name', $sectionName)->first();
            if (! $section) {
                $messages[] = "Section '{$sectionName}' was not found in class '{$className}'.";
            }
        }

        $yearValue = trim((string) ($row['academic_year'] ?? ''));
        $year = null;
        if ($yearValue !== '') {
            $year = AcademicYear::where('school_id', $schoolId)->where('year', $yearValue)->first();
            if (! $year) {
                $messages[] = "Academic year '{$yearValue}' was not found.";
            }
        } else {
            $year = AcademicYear::where('school_id', $schoolId)->current()->first();
            if (! $year) {
                $messages[] = 'No current academic year is configured for this school; specify academic_year explicitly.';
            }
        }

        if ($admissionNumber !== '' && Student::where('school_id', $schoolId)->where('admission_number', $admissionNumber)->exists()) {
            $messages[] = "Admission number '{$admissionNumber}' already exists.";
        }

        $guardianData = [];
        $guardianName = trim((string) ($row['guardian_name'] ?? ''));
        if ($guardianName !== '') {
            $relation = strtolower(trim((string) ($row['guardian_relation'] ?? '')));
            if (! in_array($relation, self::GUARDIAN_RELATIONS, true)) {
                $messages[] = 'guardian_relation must be one of: '.implode(', ', self::GUARDIAN_RELATIONS).'.';
            } else {
                $guardianData[] = [
                    'relation' => $relation,
                    'name' => $guardianName,
                    'phone' => trim((string) ($row['guardian_phone'] ?? '')) ?: null,
                ];
            }
        }

        if (! empty($messages)) {
            throw new RowImportException($messages);
        }

        try {
            $this->studentService->enrol(
                $schoolId,
                [
                    'admission_number' => $admissionNumber,
                    'name' => $name,
                    'gender' => $gender,
                    'dob' => $dob,
                    'blood_group' => $bloodGroup,
                ],
                [
                    'academic_year_id' => $year->id,
                    'class_id' => $class->id,
                    'section_id' => $section->id,
                    'roll_number' => trim((string) ($row['roll_number'] ?? '')) ?: null,
                ],
                $guardianData,
            );
        } catch (UnprocessableEntityHttpException $e) {
            // e.g. "Section is full" — same capacity check the manual API hits.
            throw new RowImportException([$e->getMessage()]);
        }
    }
}
