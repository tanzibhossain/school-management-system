<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreRoutinePeriodRequest;
use App\Modules\Academic\Http\Requests\UpdateRoutinePeriodRequest;
use App\Modules\Academic\Http\Resources\RoutinePeriodResource;
use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class RoutinePeriodController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = RoutinePeriod::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return RoutinePeriodResource::collection($items);
    }

    public function store(StoreRoutinePeriodRequest $request): RoutinePeriodResource
    {
        $item = RoutinePeriod::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new RoutinePeriodResource($item);
    }

    public function show(int $id): RoutinePeriodResource
    {
        $item = RoutinePeriod::where('school_id', app('current_school_id'))->findOrFail($id);

        return new RoutinePeriodResource($item);
    }

    public function update(UpdateRoutinePeriodRequest $request, int $id): RoutinePeriodResource
    {
        $item = RoutinePeriod::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new RoutinePeriodResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = RoutinePeriod::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
