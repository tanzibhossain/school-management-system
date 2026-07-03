<?php

namespace App\Modules\IdCard\Http\Controllers;

use App\Modules\IdCard\Http\Requests\RequestIdCardBatchRequest;
use App\Modules\IdCard\Http\Resources\IdCardBatchResource;
use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Repositories\IdCardBatchRepository;
use App\Modules\IdCard\Services\IdCardBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class IdCardBatchController extends Controller
{
    public function __construct(
        private readonly IdCardBatchService $service,
        private readonly IdCardBatchRepository $repository,
    ) {}

    /**
     * POST /v2/id-cards/batches — request a batch. Under Horizon this returns
     * immediately with status=queued; under the sync queue driver (tests, or
     * any env without Horizon running) the job has already completed by the
     * time this returns, so the response may already show completed/failed.
     */
    public function store(RequestIdCardBatchRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $batch = $this->service->request(
            $schoolId,
            $data['type'],
            $data['template_id'],
            $data['scope'],
            [
                'class_id' => $data['class_id'] ?? null,
                'section_id' => $data['section_id'] ?? null,
                'target_ids' => $data['target_ids'] ?? null,
            ],
            $request->user(),
        );

        return (new IdCardBatchResource($batch))->response()->setStatusCode(201);
    }

    /** GET /v2/id-cards/batches — this school's batch history. */
    public function index(): AnonymousResourceCollection
    {
        return IdCardBatchResource::collection(
            $this->repository->forSchool(app('current_school_id'))
        );
    }

    /** GET /v2/id-cards/batches/{id} — poll a batch's status and download links. */
    public function show(int $id): IdCardBatchResource
    {
        $batch = IdCardBatch::forSchool(app('current_school_id'))->with(['files'])->findOrFail($id);

        return new IdCardBatchResource($batch);
    }
}
