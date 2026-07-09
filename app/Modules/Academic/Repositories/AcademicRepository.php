<?php

namespace App\Modules\Academic\Repositories;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Models\AcademicVersion;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Models\RoutineRoom;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\StudentType;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Academic\Models\Transport;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;

class AcademicRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(AcademicYear::class, $cache);
    }

    // ── Academic Years ────────────────────────────────────────────────────────

    /** @return Collection<int, AcademicYear> */
    public function getYears(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:academic_years"),
            fn () => AcademicYear::where('school_id', $schoolId)->active()->orderByDesc('id')->get(),
        );
    }

    public function getCurrentYear(int $schoolId): ?AcademicYear
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:current_year"),
            fn () => AcademicYear::where('school_id', $schoolId)->current()->first(),
        );
    }

    // ── Classes ───────────────────────────────────────────────────────────────

    /** @return Collection<int, SchoolClass> */
    public function getActiveClasses(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:classes"),
            fn () => SchoolClass::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    // ── Sections ──────────────────────────────────────────────────────────────

    /** @return Collection<int, Section> */
    public function getSectionsForClass(int $schoolId, int $classId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:class:{$classId}:sections"),
            fn () => Section::where('school_id', $schoolId)->where('class_id', $classId)->active()->orderBy('name')->get(),
        );
    }

    // ── Reference Data (shifts, versions, groups, student_types, transports) ─

    /** @return Collection<int, AcademicShift> */
    public function getActiveShifts(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:shifts"),
            fn () => AcademicShift::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    /** @return Collection<int, AcademicVersion> */
    public function getActiveVersions(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:versions"),
            fn () => AcademicVersion::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    /** @return Collection<int, AcademicGroup> */
    public function getActiveGroups(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:groups"),
            fn () => AcademicGroup::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    /** @return Collection<int, Transport> */
    public function getActiveTransports(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:transports"),
            fn () => Transport::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    /** @return Collection<int, StudentType> */
    public function getActiveStudentTypes(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:student_types"),
            fn () => StudentType::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    // ── Subjects ──────────────────────────────────────────────────────────────

    /** @return Collection<int, SubjectRelation> */
    public function getSubjectsForClass(int $schoolId, int $classId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:class:{$classId}:subjects"),
            fn () => SubjectRelation::with('subject')
                ->where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->get(),
        );
    }

    // ── Routines ──────────────────────────────────────────────────────────────

    /** @return Collection<int, ClassRoutine> */
    public function getRoutineForClass(int $schoolId, int $classId, int $sectionId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:routine:class:{$classId}:section:{$sectionId}"),
            fn () => ClassRoutine::with(['subject', 'room', 'period', 'shift'])
                ->where('school_id', $schoolId)
                ->forClass($classId, $sectionId)
                ->orderBy('day_of_week')
                ->orderBy('period_id')
                ->get(),
        );
    }

    /** @return Collection<int, RoutinePeriod> */
    public function getPeriods(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:periods"),
            fn () => RoutinePeriod::where('school_id', $schoolId)->active()->orderBy('start_time')->get(),
        );
    }

    /** @return Collection<int, RoutineRoom> */
    public function getRooms(int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:rooms"),
            fn () => RoutineRoom::where('school_id', $schoolId)->active()->orderBy('name')->get(),
        );
    }

    /**
     * All dropdown data in one cached call.
     *
     * @return array<string, Collection<int, mixed>>
     */
    public function getDropdownData(int $schoolId): array
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:dropdowns"),
            fn () => [
                'classes'       => $this->getActiveClasses($schoolId),
                'shifts'        => $this->getActiveShifts($schoolId),
                'versions'      => $this->getActiveVersions($schoolId),
                'groups'        => $this->getActiveGroups($schoolId),
                'transports'    => $this->getActiveTransports($schoolId),
                'student_types' => $this->getActiveStudentTypes($schoolId),
                'current_year'  => $this->getCurrentYear($schoolId),
            ],
        );
    }

    protected function cacheTag(): string
    {
        return 'academic';
    }
}
