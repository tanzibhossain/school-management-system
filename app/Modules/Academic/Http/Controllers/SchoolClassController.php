<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreSchoolClassRequest;
use App\Modules\Academic\Http\Requests\UpdateSchoolClassRequest;
use App\Modules\Academic\Http\Resources\SchoolClassResource;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SchoolClassController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = SchoolClass::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return SchoolClassResource::collection($items);
    }

    public function store(StoreSchoolClassRequest $request): SchoolClassResource
    {
        $item = SchoolClass::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new SchoolClassResource($item);
    }

    public function show(int $id): SchoolClassResource
    {
        $item = SchoolClass::where('school_id', app('current_school_id'))->findOrFail($id);

        return new SchoolClassResource($item);
    }

    public function update(UpdateSchoolClassRequest $request, int $id): SchoolClassResource
    {
        $item = SchoolClass::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new SchoolClassResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = SchoolClass::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
