<?php

namespace App\Modules\Mark\Services;

use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies ready-made templates (config/grading.php — seed data, not code).
 * Head Teacher can edit the seeded rows afterwards.
 */
class GradeTemplateService
{
    /** Replace a class's grade boundaries with a named template. */
    public function applyGradeTemplate(int $schoolId, int $classId, string $template): int
    {
        $rows = config("grading.grade_templates.{$template}");

        if ($rows === null) {
            throw ValidationException::withMessages([
                'template' => ["Unknown grade template [{$template}]."],
            ]);
        }

        return DB::transaction(function () use ($schoolId, $classId, $rows): int {
            GradeBoundary::forClass($schoolId, $classId)->delete();

            foreach ($rows as $row) {
                GradeBoundary::create($row + ['school_id' => $schoolId, 'class_id' => $classId]);
            }

            return count($rows);
        });
    }

    /**
     * Apply a division template to one exam subject: weights are % of the
     * subject's full_marks. Blocked once marks exist (division change would
     * orphan entered marks).
     */
    public function applyDivisionTemplate(int $schoolId, int $examSubjectId, string $template): int
    {
        $spec = config("grading.division_templates.{$template}");

        if ($spec === null) {
            throw ValidationException::withMessages([
                'template' => ["Unknown division template [{$template}]."],
            ]);
        }

        $examSubject = ExamSubject::where('school_id', $schoolId)->findOrFail($examSubjectId);

        $existingDivisionIds = MarkDivision::forSchool($schoolId)
            ->where('exam_subject_id', $examSubject->id)
            ->pluck('id');

        if (Mark::whereIn('mark_division_id', $existingDivisionIds)->exists()) {
            throw ValidationException::withMessages([
                'template' => ['Marks already entered for this subject — divisions can no longer be replaced.'],
            ]);
        }

        return DB::transaction(function () use ($schoolId, $examSubject, $spec, $existingDivisionIds): int {
            MarkDivision::whereIn('id', $existingDivisionIds)->delete();

            foreach ($spec as $order => $division) {
                MarkDivision::create([
                    'school_id'       => $schoolId,
                    'exam_id'         => $examSubject->exam_id,
                    'exam_subject_id' => $examSubject->id,
                    'name'            => $division['name'],
                    'max_marks'       => round((float) $examSubject->full_marks * $division['weight'] / 100, 2),
                    'display_order'   => $order,
                ]);
            }

            return count($spec);
        });
    }
}
