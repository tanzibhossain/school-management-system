<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreAcademicVersionRequest;
use App\Modules\Academic\Http\Requests\UpdateAcademicVersionRequest;
use App\Modules\Academic\Http\Resources\AcademicVersionResource;
use App\Modules\Academic\Models\AcademicVersion;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AcademicVersionController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = AcademicVersion::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return AcademicVersionResource::collection($items);
    }

    public function store(StoreAcademicVersionRequest $request): AcademicVersionResource
    {
        $item = AcademicVersion::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new AcademicVersionResource($item);
    }

    public function show(int $id): AcademicVersionResource
    {
        $item = AcademicVersion::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AcademicVersionResource($item);
    }

    public function update(UpdateAcademicVersionRequest $request, int $id): AcademicVersionResource
    {
        $item = AcademicVersion::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new AcademicVersionResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = AcademicVersion::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
