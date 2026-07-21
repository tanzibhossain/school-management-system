<?php

namespace App\Modules\Mark\Services;

use App\Models\User;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Examination\Models\Exam;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\Staff\Models\Staff;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkEntryService
{
    /**
     * Bulk-enter marks for one division. NO cache on writes.
     * Upsert semantics — re-entering corrects, never duplicates.
     *
     * @param  array<int, array{student_id: int, marks_obtained?: float|null, is_absent?: bool}>  $entries
     * @return array{created: int, updated: int}
     */
    public function bulkEnter(int $schoolId, int $divisionId, array $entries, User $user): array
    {
        $division = MarkDivision::forSchool($schoolId)
            ->with('examSubject.subjectRelation')
            ->findOrFail($divisionId);

        $this->assertCanEnter($user, $division, $schoolId);

        $existing = Mark::forSchool($schoolId)
            ->where('mark_division_id', $division->id)
            ->whereIn('student_id', array_column($entries, 'student_id'))
            ->get()
            ->keyBy('student_id');

        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($schoolId, $division, $entries, $user, $existing, &$created, &$updated): void {
            foreach ($entries as $entry) {
                $isAbsent = (bool) ($entry['is_absent'] ?? false);
                $obtained = $isAbsent ? null : ($entry['marks_obtained'] ?? null);

                if (! $isAbsent && $obtained === null) {
                    throw ValidationException::withMessages([
                        'entries' => ["Student {$entry['student_id']}: marks_obtained is required unless absent."],
                    ]);
                }

                if ($obtained !== null && (float) $obtained > (float) $division->max_marks) {
                    throw ValidationException::withMessages([
                        'entries' => ["Student {$entry['student_id']}: {$obtained} exceeds division max of {$division->max_marks}."],
                    ]);
                }

                $current = $existing->get($entry['student_id']);

                if ($current?->isLocked()) {
                    throw ValidationException::withMessages([
                        'entries' => ["Student {$entry['student_id']}: result is locked — marks can no longer be changed."],
                    ]);
                }

                if ($current !== null) {
                    $current->update([
                        'marks_obtained' => $obtained,
                        'is_absent' => $isAbsent,
                        'entered_by' => $user->id,
                    ]);
                    $updated++;
                } else {
                    Mark::create([
                        'school_id' => $schoolId,
                        'exam_id' => $division->exam_id,
                        'student_id' => $entry['student_id'],
                        'mark_division_id' => $division->id,
                        'marks_obtained' => $obtained,
                        'is_absent' => $isAbsent,
                        'entered_by' => $user->id,
                    ]);
                    $created++;
                }
            }
        });

        return ['created' => $created, 'updated' => $updated];
    }

    /**
     * Apply grace marks — separate audited field, never mixed into marks_obtained.
     * Capped by the class's grace_marks_cap. Admin ability required (route-level).
     */
    public function applyGrace(int $schoolId, int $markId, float $grace, User $user): Mark
    {
        $mark = Mark::forSchool($schoolId)->with('division.examSubject')->findOrFail($markId);

        if ($mark->isLocked()) {
            throw ValidationException::withMessages([
                'grace_marks' => ['Result is locked — grace can no longer be applied.'],
            ]);
        }

        $exam = Exam::findOrFail($mark->exam_id);
        $settings = MarkSetting::forClass($schoolId, $exam->class_id);

        if ($grace > (float) $settings->grace_marks_cap) {
            throw ValidationException::withMessages([
                'grace_marks' => ["Grace exceeds the school cap of {$settings->grace_marks_cap}."],
            ]);
        }

        $mark->update([
            'grace_marks' => $grace,
            'grace_given_by' => $user->id,
        ]);

        return $mark->fresh();
    }

    /**
     * Admins enter anything; teachers only subjects they are assigned to teach
     * for this class (via class_routines.teacher_id + subject_id).
     */
    private function assertCanEnter(User $user, MarkDivision $division, int $schoolId): void
    {
        if ($user->tokenCan('admin:*')) {
            return;
        }

        $staff = Staff::where('school_id', $schoolId)->where('user_id', $user->id)->first();

        $exam = Exam::findOrFail($division->exam_id);
        $subjectId = $division->examSubject?->subjectRelation?->subject_id;

        // A teacher may enter marks for their assigned subject, or any subject they
        // are timetabled to teach for this class (class_routines).
        $assigned = $staff !== null
            && $subjectId !== null
            && (
                (int) $staff->subject_id === (int) $subjectId
                || ClassRoutine::where('school_id', $schoolId)
                    ->where('class_id', $exam->class_id)
                    ->where('subject_id', $subjectId)
                    ->where('teacher_id', $staff->id)
                    ->exists()
            );

        if (! $assigned) {
            throw new AuthorizationException('You can only enter marks for subjects assigned to you.');
        }
    }
}
