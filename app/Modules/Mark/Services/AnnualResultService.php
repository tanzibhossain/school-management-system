<?php

namespace App\Modules\Mark\Services;

use App\Modules\Mark\Models\ExamResult;
use App\Modules\Mark\Models\ExamWeight;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Year-end combined result: weighted aggregation across exams
 * (e.g. Half-Yearly 30% + Annual 70%) per school/class/year config.
 */
class AnnualResultService
{
    /** @return Collection<int, array<string, mixed>> one row per student, ranked */
    public function combined(int $schoolId, int $classId, int $yearId): Collection
    {
        $weights = ExamWeight::forClassYear($schoolId, $classId, $yearId)->get();

        if ($weights->isEmpty()) {
            throw ValidationException::withMessages([
                'exam_weights' => ['No exam weights configured for this class/year.'],
            ]);
        }

        $totalWeight = (float) $weights->sum('weight_percent');

        $resultsByExam = ExamResult::forSchool($schoolId)
            ->whereIn('exam_id', $weights->pluck('exam_id'))
            ->with('student:id,name,admission_number')
            ->get()
            ->groupBy('exam_id');

        // studentId => aggregate
        $students = [];

        foreach ($weights as $weight) {
            foreach ($resultsByExam->get($weight->exam_id, collect()) as $result) {
                $sid = $result->student_id;

                $students[$sid] ??= [
                    'student_id'     => $sid,
                    'student'        => [
                        'id'               => $result->student->id,
                        'name'             => $result->student->name,
                        'admission_number' => $result->student->admission_number,
                    ],
                    'weighted_percentage' => 0.0,
                    'weighted_gpa'        => 0.0,
                    'gpa_applicable'      => true,
                    'exams_counted'       => 0,
                    'is_pass'             => true,
                ];

                $w = (float) $weight->weight_percent;

                $students[$sid]['weighted_percentage'] += ((float) $result->percentage) * $w / $totalWeight;

                if ($result->gpa === null) {
                    $students[$sid]['gpa_applicable'] = false;
                } else {
                    $students[$sid]['weighted_gpa'] += ((float) $result->gpa) * $w / $totalWeight;
                }

                $students[$sid]['is_pass'] = $students[$sid]['is_pass'] && $result->is_pass;
                $students[$sid]['exams_counted']++;
            }
        }

        $expectedExams = $weights->count();

        $rows = collect($students)->map(function (array $row) use ($expectedExams) {
            // A student missing any weighted exam cannot pass the combined result
            $row['is_complete'] = $row['exams_counted'] === $expectedExams;
            $row['is_pass']     = $row['is_pass'] && $row['is_complete'];

            $row['weighted_percentage'] = round($row['weighted_percentage'], 2);
            $row['weighted_gpa']        = $row['gpa_applicable'] ? round($row['weighted_gpa'], 2) : null;
            unset($row['gpa_applicable']);

            return $row;
        })->values();

        // Rank: passed first, then weighted GPA, then weighted percentage
        $sorted = $rows->sortBy([
            fn ($a, $b) => $b['is_pass'] <=> $a['is_pass'],
            fn ($a, $b) => ($b['weighted_gpa'] ?? -1) <=> ($a['weighted_gpa'] ?? -1),
            fn ($a, $b) => $b['weighted_percentage'] <=> $a['weighted_percentage'],
        ])->values();

        return $sorted->map(function (array $row, int $index) {
            $row['position'] = $index + 1;

            return $row;
        });
    }
}
