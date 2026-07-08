<?php

namespace App\Modules\Transport\Http\Controllers;

use App\Modules\Transport\Http\Requests\AssignStudentRequest;
use App\Modules\Transport\Http\Resources\StudentTransportAssignmentResource;
use App\Modules\Transport\Repositories\StudentTransportAssignmentRepository;
use App\Modules\Transport\Services\StudentTransportAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StudentTransportAssignmentController extends Controller
{
    public function __construct(
        private readonly StudentTransportAssignmentService $service,
        private readonly StudentTransportAssignmentRepository $repository,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $rows = $this->repository->filtered(app('current_school_id'), [
            'route_id' => $request->query('route_id'),
            'status' => $request->query('status'),
        ]);

        return StudentTransportAssignmentResource::collection($rows);
    }

    public function store(AssignStudentRequest $request): JsonResponse
    {
        $assignment = $this->service->assign(app('current_school_id'), $request->validated());

        return (new StudentTransportAssignmentResource($assignment))->response()->setStatusCode(201);
    }

    public function end(int $id): StudentTransportAssignmentResource
    {
        return new StudentTransportAssignmentResource(
            $this->service->end(app('current_school_id'), $id)
        );
    }
}
