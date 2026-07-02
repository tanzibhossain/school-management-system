<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Modules\Attendance\Http\Requests\BulkStudentAttendanceRequest;
use App\Modules\Attendance\Http\Resources\StudentAttendanceResource;
use App\Modules\Attendance\Repositories\AttendanceRepository;
use App\Modules\Attendance\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StudentAttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $service,
        private readonly AttendanceRepository $repository,
    ) {}

    /** POST /v2/attendance/students/bulk — upsert a class/section register for one date. */
    public function bulkStore(BulkStudentAttendanceRequest $request): JsonResponse
    {
        $result = $this->service->bulkUpsert(
            app('current_school_id'),
            (int) $request->validated('class_id'),
            $request->validated('section_id') !== null ? (int) $request->validated('section_id') : null,
            $request->validated('date'),
            $request->validated('entries'),
            $request->user(),
        );

        return response()->json(['data' => $result], 201);
    }

    /** GET /v2/attendance/students/register?class_id=&section_id=&date= */
    public function register(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'class_id'   => ['required', 'integer'],
            'section_id' => ['nullable', 'integer'],
            'date'       => ['required', 'date_format:Y-m-d'],
        ]);

        $records = $this->repository->register(
            app('current_school_id'),
            (int) $request->query('class_id'),
            $request->query('section_id') !== null ? (int) $request->query('section_id') : null,
            $request->query('date'),
        );

        return StudentAttendanceResource::collection($records);
    }

    /** GET /v2/attendance/students/{studentId}/summary?from=&to= */
    public function summary(Request $request, int $studentId): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to'   => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $summary = $this->service->studentSummary(
            app('current_school_id'),
            $studentId,
            $request->query('from'),
            $request->query('to'),
        );

        return response()->json(['data' => $summary]);
    }
}
