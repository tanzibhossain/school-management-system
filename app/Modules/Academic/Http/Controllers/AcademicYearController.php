<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreAcademicYearRequest;
use App\Modules\Academic\Http\Requests\UpdateAcademicYearRequest;
use App\Modules\Academic\Http\Resources\AcademicYearResource;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Services\AcademicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AcademicYearController extends Controller
{
    public function __construct(private readonly AcademicService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $years = AcademicYear::where('school_id', $schoolId)->active()->orderByDesc('id')->get();

        return AcademicYearResource::collection($years);
    }

    public function store(StoreAcademicYearRequest $request): AcademicYearResource
    {
        $year = AcademicYear::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new AcademicYearResource($year);
    }

    public function show(int $id): AcademicYearResource
    {
        $year = AcademicYear::where('school_id', app('current_school_id'))->findOrFail($id);

        return new AcademicYearResource($year);
    }

    public function update(UpdateAcademicYearRequest $request, int $id): AcademicYearResource
    {
        $year = AcademicYear::where('school_id', app('current_school_id'))->findOrFail($id);
        $year->update($request->validated());

        return new AcademicYearResource($year->fresh());
    }

    public function setCurrent(int $id): AcademicYearResource
    {
        $year = $this->service->setCurrentYear(app('current_school_id'), $id);

        return new AcademicYearResource($year);
    }

    public function destroy(int $id): JsonResponse
    {
        $year = AcademicYear::where('school_id', app('current_school_id'))->findOrFail($id);
        $year->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
