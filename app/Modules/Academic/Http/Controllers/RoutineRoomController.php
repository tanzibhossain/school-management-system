<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreRoutineRoomRequest;
use App\Modules\Academic\Http\Requests\UpdateRoutineRoomRequest;
use App\Modules\Academic\Http\Resources\RoutineRoomResource;
use App\Modules\Academic\Models\RoutineRoom;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class RoutineRoomController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = RoutineRoom::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return RoutineRoomResource::collection($items);
    }

    public function store(StoreRoutineRoomRequest $request): RoutineRoomResource
    {
        $item = RoutineRoom::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new RoutineRoomResource($item);
    }

    public function show(int $id): RoutineRoomResource
    {
        $item = RoutineRoom::where('school_id', app('current_school_id'))->findOrFail($id);

        return new RoutineRoomResource($item);
    }

    public function update(UpdateRoutineRoomRequest $request, int $id): RoutineRoomResource
    {
        $item = RoutineRoom::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new RoutineRoomResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = RoutineRoom::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
