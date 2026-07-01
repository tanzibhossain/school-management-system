<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreAcademicShiftRequest;
use App\Modules\Academic\Http\Requests\UpdateAcademicShiftRequest;
use App\Modules\Academic\Http\Resources\AcademicShiftResource;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AcademicShiftController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = AcademicShift::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return AcademicShiftResource::collection($items);
    }

    public function store(StoreAcademicShiftRequest $request): AcademicShiftResource
    {
        $item = AcademicShift::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new AcademicShiftResource($item);
    }

    public function show(int $id): AcademicShiftResource
    {
        $item = AcademicShift::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AcademicShiftResource($item);
    }

    public function update(UpdateAcademicShiftRequest $request, int $id): AcademicShiftResource
    {
        $item = AcademicShift::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new AcademicShiftResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = AcademicShift::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
