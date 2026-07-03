<?php

namespace App\Modules\DataImport\Services;

use App\Modules\DataImport\Exceptions\RowImportException;
use App\Modules\DataImport\Services\Concerns\ParsesExcelDates;
use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Services\StaffService;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Resolves one spreadsheet row (name, gender, dob, designation_name,
 * department_name, joining_date, employment_type, basic_salary) into IDs,
 * validates it, then calls the SAME StaffService::hire() the normal Staff
 * API uses — an imported teacher is created under identical business rules
 * (employee ID generation) rather than a second, divergent code path.
 * Class/subject assignment is a separate call (StaffService::assign()) and
 * out of scope here, same as manual hiring via the API.
 */
class StaffImportRowService
{
    use ParsesExcelDates;

    private const GENDERS = ['male', 'female', 'other'];

    private const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    private const EMPLOYMENT_TYPES = ['permanent', 'contractual', 'part_time'];

    public function __construct(private readonly StaffService $staffService) {}

    /**
     * @param  array<string, mixed>  $row
     *
     * @throws RowImportException
     */
    public function import(int $schoolId, array $row): void
    {
        $messages = [];

        $name = trim((string) ($row['name'] ?? ''));
        $gender = strtolower(trim((string) ($row['gender'] ?? '')));

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

        $joiningDateRaw = $row['joining_date'] ?? null;
        $joiningDate = $this->parseDate($joiningDateRaw);
        if ($joiningDateRaw !== null && trim((string) $joiningDateRaw) !== '' && $joiningDate === null) {
            $messages[] = 'joining_date could not be parsed as a date.';
        }

        $designationName = trim((string) ($row['designation_name'] ?? ''));
        $designationId = null;
        if ($designationName !== '') {
            $designation = Designation::where('school_id', $schoolId)->where('name', $designationName)->first();
            if (! $designation) {
                $messages[] = "Designation '{$designationName}' was not found.";
            } else {
                $designationId = $designation->id;
            }
        }

        $departmentName = trim((string) ($row['department_name'] ?? ''));
        $departmentId = null;
        if ($departmentName !== '') {
            $department = Department::where('school_id', $schoolId)->where('name', $departmentName)->first();
            if (! $department) {
                $messages[] = "Department '{$departmentName}' was not found.";
            } else {
                $departmentId = $department->id;
            }
        }

        $employmentTypeRaw = trim((string) ($row['employment_type'] ?? ''));
        $employmentType = $employmentTypeRaw !== '' ? strtolower($employmentTypeRaw) : null;
        if ($employmentType !== null && ! in_array($employmentType, self::EMPLOYMENT_TYPES, true)) {
            $messages[] = 'employment_type must be one of: '.implode(', ', self::EMPLOYMENT_TYPES).'.';
        }

        $basicSalaryRaw = trim((string) ($row['basic_salary'] ?? ''));
        $basicSalary = null;
        if ($basicSalaryRaw !== '') {
            if (! is_numeric($basicSalaryRaw) || (float) $basicSalaryRaw < 0) {
                $messages[] = 'basic_salary must be a non-negative number.';
            } else {
                $basicSalary = (float) $basicSalaryRaw;
            }
        }

        if (! empty($messages)) {
            throw new RowImportException($messages);
        }

        try {
            $this->staffService->hire($schoolId, [
                'name' => $name,
                'gender' => $gender,
                'dob' => $dob,
                'blood_group' => $bloodGroup,
                'joining_date' => $joiningDate,
                'employment_type' => $employmentType,
                'basic_salary' => $basicSalary,
                'designation_id' => $designationId,
                'department_id' => $departmentId,
            ]);
        } catch (UnprocessableEntityHttpException $e) {
            throw new RowImportException([$e->getMessage()]);
        }
    }
}
