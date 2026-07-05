<?php

namespace App\Modules\Library\Http\Controllers;

use App\Modules\Library\Http\Requests\StoreBorrowRecordRequest;
use App\Modules\Library\Http\Resources\BorrowRecordResource;
use App\Modules\Library\Models\BorrowRecord;
use App\Modules\Library\Repositories\BorrowRecordRepository;
use App\Modules\Library\Services\BorrowRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class BorrowRecordController extends Controller
{
    public function __construct(
        private readonly BorrowRecordService $service,
        private readonly BorrowRecordRepository $repository,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $records = $this->repository->forSchool(app('current_school_id'));

        return BorrowRecordResource::collection($records);
    }

    public function show(int $id): BorrowRecordResource
    {
        $record = BorrowRecord::forSchool(app('current_school_id'))->findOrFail($id);

        return new BorrowRecordResource($record);
    }

    public function store(StoreBorrowRecordRequest $request): JsonResponse
    {
        $record = $this->service->borrow(app('current_school_id'), $request->validated());

        return (new BorrowRecordResource($record))->response()->setStatusCode(201);
    }

    public function returnBorrow(int $id): BorrowRecordResource
    {
        $record = $this->service->markReturned(app('current_school_id'), $id);

        return new BorrowRecordResource($record);
    }
}
