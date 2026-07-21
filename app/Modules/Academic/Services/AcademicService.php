<?php

namespace App\Modules\Academic\Services;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Academic\Repositories\AcademicRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class AcademicService extends BaseService
{
    public function __construct(
        AcademicRepository $repository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Set a year as the active current year (flips all others to false).
     */
    public function setCurrentYear(int $schoolId, int $yearId): AcademicYear
    {
        /** @var AcademicYear $year */
        $year = AcademicYear::where('school_id', $schoolId)->findOrFail($yearId);

        DB::transaction(function () use ($schoolId, $year): void {
            AcademicYear::where('school_id', $schoolId)->update(['is_current' => false]);
            $year->update(['is_current' => true]);
        });

        $this->repository->flush();

        return $year->fresh();
    }

    /**
     * All reference dropdown data for front-end forms.
     *
     * @return array<string, mixed>
     */
    public function getDropdownData(int $schoolId): array
    {
        return $this->repository->getDropdownData($schoolId);
    }

    /**
     * Sync subject relations for a class (replaces existing list).
     *
     * @param  array<int, array{subject_id: int, group_id: int|null}>  $relations
     */
    public function syncSubjectRelations(int $schoolId, int $classId, array $relations): void
    {
        DB::transaction(function () use ($schoolId, $classId, $relations): void {
            SubjectRelation::where('school_id', $schoolId)->where('class_id', $classId)->delete();

            foreach ($relations as $rel) {
                SubjectRelation::create([
                    'school_id' => $schoolId,
                    'class_id' => $classId,
                    'subject_id' => $rel['subject_id'],
                    'group_id' => $rel['group_id'] ?? null,
                ]);
            }
        });

        $this->repository->flush();
    }

    /**
     * Soft-delete (trash) any academic entity by marking is_trash = true.
     */
    public function trash(object $model): void
    {
        if (! property_exists($model, 'is_trash') && ! isset($model->is_trash)) {
            throw new \InvalidArgumentException('Model does not support soft-delete via is_trash.');
        }

        $model->update(['is_trash' => true]);
        $this->repository->flush();
    }

    /**
     * Restore a trashed academic entity.
     */
    public function restore(object $model): void
    {
        $model->update(['is_trash' => false]);
        $this->repository->flush();
    }
}
