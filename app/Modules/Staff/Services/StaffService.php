<?php

namespace App\Modules\Staff\Services;

use App\Modules\Staff\Events\StaffAssignedToClass;
use App\Modules\Staff\Events\StaffHired;
use App\Modules\Staff\Events\StaffReHired;
use App\Modules\Staff\Events\StaffTerminated;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Models\StaffAcademic;
use App\Modules\Staff\Repositories\StaffRepository;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class StaffService extends BaseService
{
    public function __construct(
        StaffRepository $repository,
        private readonly StaffIdGeneratorService $idGenerator,
    ) {
        parent::__construct($repository);
    }

    /**
     * Hire a new staff member: generate employee ID, create record, fire event.
     *
     * @param array<string, mixed> $data
     */
    public function hire(int $schoolId, array $data): Staff
    {
        return DB::transaction(function () use ($schoolId, $data): Staff {
            $employeeId = $this->idGenerator->generate($schoolId);

            $staff = Staff::create(array_merge($data, [
                'school_id'   => $schoolId,
                'employee_id' => $employeeId,
                'status'      => 'active',
            ]));

            event(new StaffHired($staff));

            return $staff->load(['designation', 'department']);
        });
    }

    /**
     * Assign a staff member to a class/section for a given academic year.
     *
     * @param array<string, mixed> $data {academic_year_id, class_id, section_id, subject, is_class_teacher}
     */
    public function assign(Staff $staff, array $data): StaffAcademic
    {
        // Enforce one class teacher per section per year
        if (! empty($data['is_class_teacher']) && $data['is_class_teacher']) {
            $existing = StaffAcademic::where('school_id', $staff->school_id)
                ->where('academic_year_id', $data['academic_year_id'])
                ->where('class_id', $data['class_id'])
                ->where('section_id', $data['section_id'] ?? null)
                ->where('is_class_teacher', true)
                ->exists();

            if ($existing) {
                throw new UnprocessableEntityHttpException(
                    'A class teacher is already assigned to this section for the selected year.'
                );
            }
        }

        $academic = StaffAcademic::create(array_merge($data, [
            'school_id' => $staff->school_id,
            'staff_id'  => $staff->id,
        ]));

        $this->repository->flush();
        event(new StaffAssignedToClass($staff, $academic));

        return $academic;
    }

    /**
     * Terminate a staff member — sets status, revokes portal tokens.
     */
    public function terminate(Staff $staff): Staff
    {
        DB::transaction(function () use ($staff): void {
            // Revoke portal login if linked
            if ($staff->user_id) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->where('tokenable_id', $staff->user_id)
                    ->delete();
            }

            $staff->update(['status' => 'terminated', 'leaving_date' => now()->toDateString()]);
        });

        $this->repository->flush();
        event(new StaffTerminated($staff->fresh()));

        return $staff->fresh();
    }

    /**
     * Re-hire a previously terminated/resigned staff member.
     * Reuses the existing record and employee_id; increments re_hire_count.
     *
     * @param array<string, mixed> $data  {joining_date, employment_type, designation_id, department_id, basic_salary}
     */
    public function reHire(Staff $staff, array $data): Staff
    {
        return DB::transaction(function () use ($staff, $data): Staff {
            $staff->increment('re_hire_count');
            $staff->update(array_merge($data, [
                'status'       => 'active',
                'leaving_date' => null,
                'is_trash'     => false,
            ]));

            $this->repository->flush();
            event(new StaffReHired($staff->fresh()));

            return $staff->fresh(['designation', 'department']);
        });
    }

    /**
     * Soft-delete (trash) a staff record.
     */
    public function trash(Staff $staff): Staff
    {
        $staff->update(['is_trash' => true]);
        $this->repository->flush();

        return $staff->fresh();
    }

    /**
     * Store staff photo in MinIO and update the record.
     */
    public function uploadPhoto(Staff $staff, UploadedFile $file): Staff
    {
        $path = $file->store("staff/{$staff->school_id}/photos", 'minio');
        $staff->update(['photo' => $path]);
        $this->repository->flush();

        return $staff->fresh();
    }
}
