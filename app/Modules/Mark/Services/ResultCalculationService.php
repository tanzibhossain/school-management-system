<?php

namespace App\Modules\Mark\Services;

use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\Mark\Strategies\ResultStrategyFactory;
use App\Modules\Mark\Support\GradeResolver;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentSubject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultCalculationService
{
    /**
     * Calculate and PERSIST results for every student of the exam's class.
     * Locked results are never recomputed. Returns number of results written.
     */
    public function calculateForExam(int $schoolId, int $examId): int
    {
        $exam = Exam::where('school_id', $schoolId)->findOrFail($examId);

        $settings   = MarkSetting::forClass($schoolId, $exam->class_id);
        $boundaries = GradeBoundary::forClass($schoolId, $exam->class_id)->get();

        if ($boundaries->isEmpty()) {
            throw ValidationException::withMessages([
                'grade_boundaries' => ['No grade boundaries for this class — apply a grade template first.'],
            ]);
        }

        $examSubjects = ExamSubject::where('exam_id', $exam->id)
            ->with('subjectRelation.subject')
            ->get();

        $divisions = MarkDivision::forSchool($schoolId)
            ->where('exam_id', $exam->id)
            ->get()
            ->groupBy('exam_subject_id');

        if ($examSubjects->isEmpty() || $divisions->isEmpty()) {
            throw ValidationException::withMessages([
                'exam' => ['Exam has no subjects/divisions configured for mark entry.'],
            ]);
        }

        $studentIds = StudentAcademic::where('school_id', $schoolId)
            ->where('class_id', $exam->class_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->pluck('student_id');

        $enrollments = StudentSubject::forSchool($schoolId)
            ->where('academic_year_id', $exam->academic_year_id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        $allMarks = Mark::forSchool($schoolId)
            ->where('exam_id', $exam->id)
            ->get()
            ->groupBy(['student_id', 'mark_division_id']);

        $strategy = ResultStrategyFactory::make($settings->result_strategy);

        // ── Per-student computation ─────────────────────────────────────────
        $computed = [];

        foreach ($studentIds as $studentId) {
            $units = $this->subjectUnits(
                $examSubjects,
                $divisions,
                $allMarks->get($studentId, collect()),
                $enrollments->get($studentId),
                $boundaries,
            );

            $applicable = $units->where('not_applicable', false)->values();
            $overall    = $strategy->calculate($applicable, $boundaries);

            $obtained = $applicable->sum('obtained');
            $possible = $applicable->sum('possible');

            $computed[$studentId] = [
                'units'      => $units,
                'obtained'   => round($obtained, 2),
                'possible'   => round($possible, 2),
                'percentage' => $possible > 0 ? round(($obtained / $possible) * 100, 2) : 0.0,
                'gpa'        => $overall['gpa'],
                'grade'      => $overall['grade'],
                'is_pass'    => $overall['is_pass'],
            ];
        }

        $this->assignMeritPositions($computed);

        // ── Persist (skip locked) — no cache on writes ──────────────────────
        $written = 0;

        DB::transaction(function () use ($schoolId, $exam, $computed, &$written): void {
            foreach ($computed as $studentId => $result) {
                $existing = ExamResult::where('exam_id', $exam->id)
                    ->where('student_id', $studentId)
                    ->first();

                if ($existing?->is_locked) {
                    continue;
                }

                ExamResult::updateOrCreate(
                    ['exam_id' => $exam->id, 'student_id' => $studentId],
                    [
                        'school_id'         => $schoolId,
                        'total_marks'       => $result['obtained'],
                        'total_possible'    => $result['possible'],
                        'percentage'        => $result['percentage'],
                        'grade'             => $result['grade'],
                        'gpa'               => $result['gpa'],
                        'is_pass'           => $result['is_pass'],
                        'merit_position'    => $result['merit_position'],
                        'subject_breakdown' => $result['units']->values()->all(),
                        'calculated_at'     => now(),
                    ],
                );
                $written++;
            }
        });

        Cache::tags(['tabulation'])->flush();

        return $written;
    }

    /** Lock all results + marks for an exam (Moderator approval). */
    public function lock(int $schoolId, int $examId, int $userId): int
    {
        $count = ExamResult::forSchool($schoolId)
            ->where('exam_id', $examId)
            ->update(['is_locked' => true, 'locked_by' => $userId]);

        if ($count === 0) {
            throw ValidationException::withMessages([
                'exam' => ['No results to lock — calculate results first.'],
            ]);
        }

        Mark::forSchool($schoolId)->where('exam_id', $examId)
            ->whereNull('locked_at')
            ->update(['locked_at' => now()]);

        return $count;
    }

    /** Tabulation sheet — persisted results, cached 30 min, flushed by MarkObserver. */
    public function tabulation(int $schoolId, int $examId): Collection
    {
        return Cache::tags(['tabulation'])->remember(
            "tabulation:school:{$schoolId}:exam:{$examId}",
            1800,
            fn () => ExamResult::forSchool($schoolId)
                ->where('exam_id', $examId)
                ->with('student:id,name,admission_number')
                ->orderByRaw('merit_position IS NULL, merit_position')
                ->get(),
        );
    }

    // ── Subject unit computation ─────────────────────────────────────────────

    /**
     * Build per-subject units for one student, merging combined groups
     * (e.g. Bangla 1st + 2nd paper) into single units.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function subjectUnits(
        Collection $examSubjects,
        Collection $divisionsBySubject,
        Collection $studentMarks,
        ?Collection $enrollment,
        Collection $boundaries,
    ): Collection {
        $hasEnrollmentData = $enrollment !== null && $enrollment->isNotEmpty();

        // Raw per-exam-subject computation
        $raw = $examSubjects->map(function (ExamSubject $examSubject) use ($divisionsBySubject, $studentMarks, $enrollment, $hasEnrollmentData) {
            $divisions = $divisionsBySubject->get($examSubject->id, collect());
            $possible  = (float) $divisions->sum('max_marks');

            $enrollmentRow = $hasEnrollmentData
                ? $enrollment->firstWhere('subject_relation_id', $examSubject->subject_relation_id)
                : null;

            $obtained         = 0.0;
            $isAbsent         = false;
            $hasMarks         = false;
            $divisionPassFail = false;

            foreach ($divisions as $division) {
                $mark = $studentMarks->get($division->id)?->first();

                if ($mark === null) {
                    continue;
                }

                $hasMarks = true;

                if ($mark->is_absent) {
                    $isAbsent = true;
                    continue;
                }

                $effective = $mark->effectiveMarks();
                $obtained += $effective;

                if ($division->pass_mark !== null && $effective < (float) $division->pass_mark) {
                    $divisionPassFail = true; // v1 individual_attribute rule: each division with a pass mark must be passed
                }
            }

            // Not enrolled and no marks → N/A (excluded from all calculations)
            $notApplicable = $hasEnrollmentData && $enrollmentRow === null && ! $hasMarks;

            return [
                'exam_subject_id' => $examSubject->id,
                'subject_name'    => $examSubject->subjectRelation?->subject?->name,
                'combined_group'  => $examSubject->combined_group,
                'pass_marks'      => (float) $examSubject->pass_marks,
                'is_optional'     => (bool) ($enrollmentRow?->is_optional ?? false),
                'not_applicable'  => $notApplicable,
                'is_absent'       => $isAbsent,
                'has_marks'       => $hasMarks,
                'division_fail'   => $divisionPassFail,
                'obtained'        => round($obtained, 2),
                'possible'        => $possible,
            ];
        });

        // Merge combined groups into single units; standalone subjects pass through
        $units = collect();

        [$grouped, $standalone] = $raw->partition(fn ($u) => $u['combined_group'] !== null);

        foreach ($standalone as $unit) {
            $units->push($this->finaliseUnit($unit, $boundaries));
        }

        foreach ($grouped->groupBy('combined_group') as $members) {
            $merged = [
                'exam_subject_id' => $members->pluck('exam_subject_id')->all(),
                'subject_name'    => $members->pluck('subject_name')->filter()->implode(' + '),
                'combined_group'  => $members->first()['combined_group'],
                'pass_marks'      => $members->sum('pass_marks'),   // combined pass mark (v1 rule)
                'is_optional'     => $members->contains(fn ($m) => $m['is_optional']),
                'not_applicable'  => ! $members->contains(fn ($m) => ! $m['not_applicable']),
                'is_absent'       => $members->contains(fn ($m) => $m['is_absent']),
                'has_marks'       => $members->contains(fn ($m) => $m['has_marks']),
                'division_fail'   => $members->contains(fn ($m) => $m['division_fail']),
                'obtained'        => round($members->sum('obtained'), 2),
                'possible'        => $members->sum('possible'),
            ];

            $units->push($this->finaliseUnit($merged, $boundaries));
        }

        return $units;
    }

    /** Resolve pass/percentage/grade for one unit. Absent ≠ zero: shown as "Ab", but never passes. */
    private function finaliseUnit(array $unit, Collection $boundaries): array
    {
        $pct = $unit['possible'] > 0 ? round(($unit['obtained'] / $unit['possible']) * 100, 2) : 0.0;

        $isPass = ! $unit['not_applicable']
            && ! $unit['is_absent']
            && $unit['has_marks']
            && ! $unit['division_fail']
            && $unit['obtained'] >= $unit['pass_marks'];

        if ($unit['not_applicable']) {
            $grade    = null;
            $gpaPoint = null;
        } elseif ($isPass) {
            $resolved = GradeResolver::byPercentage($boundaries, $pct);
            $grade    = $resolved['grade'];
            $gpaPoint = $resolved['gpa_point'];
        } else {
            $grade    = GradeResolver::byGpaPoint($boundaries, 0.0)
                ?? GradeResolver::byPercentage($boundaries, 0.0)['grade'];
            $gpaPoint = 0.0;
        }

        return [
            'exam_subject_id' => $unit['exam_subject_id'],
            'subject_name'    => $unit['subject_name'],
            'is_optional'     => $unit['is_optional'],
            'not_applicable'  => $unit['not_applicable'],
            'is_absent'       => $unit['is_absent'],
            'display_mark'    => $unit['not_applicable'] ? 'N/A' : ($unit['is_absent'] ? 'Ab' : $unit['obtained']),
            'obtained'        => $unit['obtained'],
            'possible'        => $unit['possible'],
            'percentage'      => $pct,
            'grade'           => $grade,
            'gpa_point'       => $gpaPoint,
            'is_pass'         => $isPass,
        ];
    }

    /**
     * Merit positions: passed ranked before failed; sort GPA → total → percentage;
     * identical tuples share the same position (v1 tie rule).
     */
    private function assignMeritPositions(array &$computed): void
    {
        $rankable = collect($computed)->map(fn ($r, $id) => [
            'student_id' => $id,
            'is_pass'    => $r['is_pass'],
            'gpa'        => $r['gpa'] ?? -1.0,
            'total'      => $r['obtained'],
            'pct'        => $r['percentage'],
        ])->values();

        $sorted = $rankable
            ->sortBy([
                fn ($a, $b) => $b['is_pass'] <=> $a['is_pass'],
                fn ($a, $b) => $b['gpa'] <=> $a['gpa'],
                fn ($a, $b) => $b['total'] <=> $a['total'],
                fn ($a, $b) => $b['pct'] <=> $a['pct'],
            ])
            ->values();

        $position = 0;
        $previous = null;

        foreach ($sorted as $index => $row) {
            $tuple = [$row['is_pass'], $row['gpa'], $row['total'], $row['pct']];

            if ($tuple !== $previous) {
                $position = $index + 1;
                $previous = $tuple;
            }

            $computed[$row['student_id']]['merit_position'] = $position;
        }
    }
}
