<?php

namespace App\Modules\Student\Repositories;

use App\Modules\Student\Models\Student;
use App\Repositories\BaseRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StudentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Student::class, $cache);
    }

    protected function cacheTag(): string
    {
        return 'students';
    }

    /**
     * Paginated list for admin — filterable by class, section, status, year.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(int $schoolId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Student::with(['currentAcademic.schoolClass', 'currentAcademic.section', 'primaryGuardian'])
            ->where('school_id', $schoolId)
            ->where('is_trash', false);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['class_id'])) {
            $query->whereHas('currentAcademic', fn ($q) => $q->where('class_id', $filters['class_id']));
        }
        if (! empty($filters['section_id'])) {
            $query->whereHas('currentAcademic', fn ($q) => $q->where('section_id', $filters['section_id']));
        }
        if (! empty($filters['academic_year_id'])) {
            $query->whereHas('currentAcademic', fn ($q) => $q->where('academic_year_id', $filters['academic_year_id']));
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%")
                ->orWhere('admission_number', 'like', "%{$search}%")
            );
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Active students in a specific class + section for a given year.
     *
     * @return Collection<int, Student>
     */
    public function activeByClassSection(int $schoolId, int $classId, int $sectionId, int $yearId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:year:{$yearId}:class:{$classId}:section:{$sectionId}:active"),
            fn () => Student::with('currentAcademic')
                ->where('school_id', $schoolId)
                ->where('status', 'active')
                ->where('is_trash', false)
                ->whereHas('currentAcademic', fn ($q) => $q
                    ->where('class_id', $classId)
                    ->where('section_id', $sectionId)
                    ->where('academic_year_id', $yearId)
                    ->where('is_current', true)
                )
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Count active students in a section for a given year (capacity check).
     */
    public function countInSection(int $sectionId, int $yearId): int
    {
        return Student::where('status', 'active')
            ->where('is_trash', false)
            ->whereHas('currentAcademic', fn ($q) => $q
                ->where('section_id', $sectionId)
                ->where('academic_year_id', $yearId)
                ->where('is_current', true)
            )
            ->count();
    }

    /**
     * Students whose primary guardian has the given user_id (parent portal).
     *
     * @return Collection<int, Student>
     */
    public function byGuardianUser(int $userId, int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:guardian_user:{$userId}"),
            fn () => Student::with(['currentAcademic.schoolClass', 'currentAcademic.section', 'primaryGuardian'])
                ->where('school_id', $schoolId)
                ->where('is_trash', false)
                ->whereHas('guardians', fn ($q) => $q->where('user_id', $userId))
                ->get(),
        );
    }

    /**
     * Find by admission_number within a school.
     */
    public function findByAdmissionNumber(string $admissionNumber, int $schoolId): ?Student
    {
        return Student::where('school_id', $schoolId)
            ->where('admission_number', $admissionNumber)
            ->first();
    }

    /**
     * Find by student_id (generated) within a school.
     */
    public function findByStudentId(string $studentId, int $schoolId): ?Student
    {
        return Student::where('school_id', $schoolId)
            ->where('student_id', $studentId)
            ->first();
    }
}
