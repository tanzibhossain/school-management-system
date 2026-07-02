<?php

namespace App\Modules\Mark\Http\Controllers;

use App\Modules\Mark\Http\Requests\ApplyGraceRequest;
use App\Modules\Mark\Http\Requests\BulkMarkEntryRequest;
use App\Modules\Mark\Http\Resources\MarkResource;
use App\Modules\Mark\Repositories\MarkRepository;
use App\Modules\Mark\Services\MarkEntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class MarkEntryController extends Controller
{
    public function __construct(
        private readonly MarkEntryService $service,
        private readonly MarkRepository $repository,
    ) {}

    /** POST /v2/marks/enter — bulk upsert marks for one division. */
    public function bulkStore(BulkMarkEntryRequest $request): JsonResponse
    {
        $result = $this->service->bulkEnter(
            app('current_school_id'),
            (int) $request->validated('mark_division_id'),
            $request->validated('entries'),
            $request->user(),
        );

        return response()->json(['data' => $result], 201);
    }

    /** GET /v2/marks/divisions/{divisionId}/marks — entry sheet view. */
    public function forDivision(int $divisionId): AnonymousResourceCollection
    {
        return MarkResource::collection(
            $this->repository->forDivision(app('current_school_id'), $divisionId)
        );
    }

    /** POST /v2/marks/{markId}/grace — audited grace marks (admin only). */
    public function applyGrace(ApplyGraceRequest $request, int $markId): MarkResource
    {
        $mark = $this->service->applyGrace(
            app('current_school_id'),
            $markId,
            (float) $request->validated('grace_marks'),
            $request->user(),
        );

        return new MarkResource($mark);
    }
}
