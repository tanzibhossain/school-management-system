<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreAcademicGroupRequest;
use App\Modules\Academic\Http\Requests\UpdateAcademicGroupRequest;
use App\Modules\Academic\Http\Resources\AcademicGroupResource;
use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AcademicGroupController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = AcademicGroup::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return AcademicGroupResource::collection($items);
    }

    public function store(StoreAcademicGroupRequest $request): AcademicGroupResource
    {
        $item = AcademicGroup::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new AcademicGroupResource($item);
    }

    public function show(int $id): AcademicGroupResource
    {
        $item = AcademicGroup::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AcademicGroupResource($item);
    }

    public function update(UpdateAcademicGroupRequest $request, int $id): AcademicGroupResource
    {
        $item = AcademicGroup::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new AcademicGroupResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = AcademicGroup::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
