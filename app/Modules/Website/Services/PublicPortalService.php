<?php

namespace App\Modules\Website\Services;

use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Announcement\Repositories\AnnouncementRepository;
use App\Modules\Examination\Models\Exam;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageRedirect;
use App\Modules\Website\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Read-only aggregation for the public website's "dynamic blocks" — same
 * pattern Report already established: reads other modules' existing
 * data/repositories directly, never modifies them. Nothing here is cached
 * yet (the DevPlan suggests a long TTL — worth adding once this is proven
 * out, not required for a correct first pass).
 */
class PublicPortalService
{
    public function __construct(
        private readonly AnnouncementRepository $announcements,
        private readonly SiteLayoutService $siteLayouts,
    ) {}

    public function pageBySlug(int $schoolId, string $slug): ?Page
    {
        return Page::forSchool($schoolId)
            ->published()
            ->where('slug', $slug)
            ->with(['publishedLayout'])
            ->first();
    }

    /** @return array{header: mixed, footer: mixed, settings: SiteSetting} */
    public function siteChrome(int $schoolId): array
    {
        return [
            'header' => $this->siteLayouts->published($schoolId, 'header'),
            'footer' => $this->siteLayouts->published($schoolId, 'footer'),
            'settings' => SiteSetting::forSchool($schoolId),
        ];
    }

    /** Follows a short redirect chain (old_slug -> new_slug -> new_slug -> ...), capped to avoid loops. */
    public function resolveRedirect(int $schoolId, string $slug): ?string
    {
        $current = $slug;
        $resolved = null;

        for ($hop = 0; $hop < 5; $hop++) {
            $redirect = PageRedirect::forSchool($schoolId)->where('old_slug', $current)->latest('created_at')->first();
            if (! $redirect) {
                break;
            }
            $resolved = $redirect->new_slug;
            $current = $redirect->new_slug;
        }

        return $resolved;
    }

    /** @return Collection<int, \App\Modules\Announcement\Models\Announcement> */
    public function notices(int $schoolId): Collection
    {
        return $this->announcements->listVisible($schoolId, ['all']);
    }

    /**
     * @param  array{designation_id?: int, department_id?: int}  $filters
     * @return Collection<int, Staff>
     */
    public function staffList(int $schoolId, array $filters = []): Collection
    {
        return Staff::where('school_id', $schoolId)
            ->active()
            ->when($filters['designation_id'] ?? null, fn ($q, $id) => $q->where('designation_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->with(['designation', 'department'])
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, ClassRoutine> */
    public function classRoutine(int $schoolId, int $classId, int $sectionId): Collection
    {
        return ClassRoutine::where('school_id', $schoolId)
            ->forClass($classId, $sectionId)
            ->with(['subject', 'period', 'room'])
            ->get();
    }

    /** @return array{active_students: int, active_staff: int} */
    public function stats(int $schoolId): array
    {
        return [
            'active_students' => Student::where('school_id', $schoolId)->active()->count(),
            'active_staff' => Staff::where('school_id', $schoolId)->active()->count(),
        ];
    }

    /**
     * Public result lookup — a visitor identifies themselves by roll number
     * within the exam's own class/year (Exam already carries class_id +
     * academic_year_id), not a login. Only LOCKED results are ever exposed
     * (matches Mark's "no recompute-on-read for locked results" rule), and
     * only from a published exam.
     *
     * @return array<string, mixed>|null
     */
    public function checkResult(int $schoolId, int $examId, string $rollNumber): ?array
    {
        $exam = Exam::where('school_id', $schoolId)->where('id', $examId)->published()->first();
        if (! $exam) {
            return null;
        }

        $academic = StudentAcademic::where('school_id', $schoolId)
            ->where('class_id', $exam->class_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('roll_number', $rollNumber)
            ->first();
        if (! $academic) {
            return null;
        }

        $result = ExamResult::forSchool($schoolId)
            ->where('exam_id', $examId)
            ->where('student_id', $academic->student_id)
            ->where('is_locked', true)
            ->first();
        if (! $result) {
            return null;
        }

        return [
            'total_marks' => $result->total_marks,
            'total_possible' => $result->total_possible,
            'percentage' => $result->percentage,
            'grade' => $result->grade,
            'gpa' => $result->gpa,
            'is_pass' => $result->is_pass,
            'merit_position' => $result->merit_position,
        ];
    }
}
