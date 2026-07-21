<?php

namespace App\Modules\Mark\Http\Controllers;

use App\Modules\Mark\Http\Requests\EnrollStudentSubjectsRequest;
use App\Modules\Mark\Http\Requests\StoreExamWeightsRequest;
use App\Modules\Mark\Http\Resources\ExamWeightResource;
use App\Modules\Mark\Models\ExamWeight;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ExamWeightController extends Controller
{
    /** PUT /v2/marks/exam-weights — replace the weight set for a class/year. */
    public function upsert(StoreExamWeightsRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $classId = (int) $request->validated('class_id');
        $yearId = (int) $request->validated('academic_year_id');

        DB::transaction(function () use ($request, $schoolId, $classId, $yearId): void {
            ExamWeight::forClassYear($schoolId, $classId, $yearId)->delete();

            foreach ($request->validated('weights') as $weight) {
                ExamWeight::create([
                    'school_id' => $schoolId,
                    'class_id' => $classId,
                    'academic_year_id' => $yearId,
                    'exam_id' => $weight['exam_id'],
                    'weight_percent' => $weight['weight_percent'],
                ]);
            }
        });

        return response()->json(['data' => ['weights_saved' => count($request->validated('weights'))]], 201);
    }

    /** GET /v2/marks/exam-weights?class_id=&academic_year_id= */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'class_id' => ['required', 'integer'],
            'academic_year_id' => ['required', 'integer'],
        ]);

        return ExamWeightResource::collection(
            ExamWeight::forClassYear(
                app('current_school_id'),
                (int) $request->query('class_id'),
                (int) $request->query('academic_year_id'),
            )->get()
        );
    }

    /** PUT /v2/marks/student-subjects — replace one student's enrollment for a year. */
    public function enrollStudent(EnrollStudentSubjectsRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $studentId = (int) $request->validated('student_id');
        $yearId = (int) $request->validated('academic_year_id');

        Student::where('school_id', $schoolId)->findOrFail($studentId);

        DB::transaction(function () use ($request, $schoolId, $studentId, $yearId): void {
            StudentSubject::forSchool($schoolId)
                ->where('student_id', $studentId)
                ->where('academic_year_id', $yearId)
                ->delete();

            foreach ($request->validated('subjects') as $subject) {
                StudentSubject::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'academic_year_id' => $yearId,
                    'subject_relation_id' => $subject['subject_relation_id'],
                    'is_optional' => (bool) ($subject['is_optional'] ?? false),
                ]);
            }
        });

        return response()->json(['data' => ['subjects_enrolled' => count($request->validated('subjects'))]], 201);
    }
}
