<?php

namespace App\Modules\FeeItem\Http\Controllers;

use App\Modules\FeeItem\Http\Requests\StoreFeeItemRequest;
use App\Modules\FeeItem\Http\Requests\UpdateFeeItemRequest;
use App\Modules\FeeItem\Http\Resources\FeeItemCollection;
use App\Modules\FeeItem\Http\Resources\FeeItemResource;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\FeeItem\Repositories\FeeItemRepository;
use App\Modules\FeeItem\Services\FeeItemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FeeItemController extends Controller
{
    public function __construct(
        private readonly FeeItemService $service,
        private readonly FeeItemRepository $repository,
    ) {}

    public function index(Request $request): FeeItemCollection
    {
        $items = $this->repository->paginate(
            app('current_school_id'),
            $request->only(['academic_year_id', 'class_id', 'category_id', 'frequency', 'is_active']),
        );

        return new FeeItemCollection($items);
    }

    public function store(StoreFeeItemRequest $request): JsonResponse
    {
        $item = $this->service->make(app('current_school_id'), $request->validated());

        return (new FeeItemResource($item))->response()->setStatusCode(201);
    }

    public function show(int $id): FeeItemResource
    {
        $item = FeeItem::where('school_id', app('current_school_id'))
            ->with('category')
            ->findOrFail($id);

        return new FeeItemResource($item);
    }

    public function update(UpdateFeeItemRequest $request, int $id): FeeItemResource
    {
        $item = FeeItem::where('school_id', app('current_school_id'))->findOrFail($id);

        return new FeeItemResource($this->service->modify($item, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $item = FeeItem::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->service->deactivate($item);

        return response()->json(['message' => 'Fee item deactivated.']);
    }

    public function duplicate(Request $request): JsonResponse
    {
        $request->validate([
            'from_academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'to_academic_year_id'   => ['required', 'integer', 'exists:academic_years,id', 'different:from_academic_year_id'],
        ]);

        $count = $this->service->duplicateToYear(
            app('current_school_id'),
            $request->integer('from_academic_year_id'),
            $request->integer('to_academic_year_id'),
        );

        return response()->json(['message' => "{$count} fee items duplicated."]);
    }
}
