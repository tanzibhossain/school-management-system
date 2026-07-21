<?php

namespace App\Modules\Staff\Http\Controllers;

use App\Modules\Staff\Http\Requests\AssignStaffRequest;
use App\Modules\Staff\Http\Resources\StaffAcademicResource;
use App\Modules\Staff\Models\StaffAcademic;
use App\Modules\Staff\Repositories\StaffRepository;
use App\Modules\Staff\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StaffAcademicController extends Controller
{
    public function __construct(
        private readonly StaffService $service,
        private readonly StaffRepository $repository,
    ) {}

    public function index(int $staffId): AnonymousResourceCollection
    {
        $staff = $this->repository->findOrFail($staffId, app('current_school_id'));
        $academics = $staff->academics()->orderByDesc('academic_year_id')->get();

        return StaffAcademicResource::collection($academics);
    }

    public function store(AssignStaffRequest $request, int $staffId): JsonResponse
    {
        $staff = $this->repository->findOrFail($staffId, app('current_school_id'));
        $academic = $this->service->assign($staff, $request->validated());

        return (new StaffAcademicResource($academic))->response()->setStatusCode(201);
    }

    public function destroy(int $staffId, int $academicId): JsonResponse
    {
        $this->repository->findOrFail($staffId, app('current_school_id'));

        StaffAcademic::where('staff_id', $staffId)
            ->where('id', $academicId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'Assignment removed.']);
    }
}
