<?php

namespace App\Modules\Certificate\Http\Controllers;

use App\Modules\Certificate\Http\Requests\GenerateAdmitCardRequest;
use App\Modules\Certificate\Http\Resources\AdmitCardResource;
use App\Modules\Certificate\Repositories\AdmitCardRepository;
use App\Modules\Certificate\Services\AdmitCardService;
use App\Modules\Examination\Models\Exam;
use App\Modules\Student\Models\Student;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AdmitCardController extends Controller
{
    public function __construct(
        private readonly AdmitCardService $service,
        private readonly AdmitCardRepository $repository,
    ) {}

    /** POST /v2/certificates/admit-cards/{studentId} — generate (or regenerate) an admit card. */
    public function store(GenerateAdmitCardRequest $request, int $studentId): AdmitCardResource
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);
        $exam = Exam::where('school_id', $schoolId)->findOrFail($request->validated('exam_id'));

        $admitCard = $this->service->generate($schoolId, $student, $exam, $request->user());

        return new AdmitCardResource($admitCard->load('exam'));
    }

    /** GET /v2/certificates/admit-cards/{studentId} — one student's admit cards. */
    public function index(int $studentId): AnonymousResourceCollection
    {
        return AdmitCardResource::collection(
            $this->repository->forStudent(app('current_school_id'), $studentId)
        );
    }
}
