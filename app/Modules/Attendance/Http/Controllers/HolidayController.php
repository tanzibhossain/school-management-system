<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Modules\Attendance\Http\Requests\StoreHolidayRequest;
use App\Modules\Attendance\Http\Resources\HolidayResource;
use App\Modules\Attendance\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class HolidayController extends Controller
{
    /** GET /v2/attendance/holidays?year= */
    public function index(Request $request): AnonymousResourceCollection
    {
        $holidays = Holiday::forSchool(app('current_school_id'))
            ->when($request->query('year'), fn ($q, $year) => $q->forYear((int) $year))
            ->orderBy('date')
            ->get();

        return HolidayResource::collection($holidays);
    }

    /**
     * POST /v2/attendance/holidays — also the retroactive "void day":
     * adding a 'closure' for an already-marked date excludes it from all % calculations.
     */
    public function store(StoreHolidayRequest $request): HolidayResource
    {
        $holiday = Holiday::updateOrCreate(
            [
                'school_id' => app('current_school_id'),
                'date' => $request->validated('date'),
            ],
            [
                'name' => $request->validated('name'),
                'type' => $request->validated('type') ?? 'school',
                'created_by' => $request->user()->id,
            ],
        );

        return new HolidayResource($holiday);
    }

    /** DELETE /v2/attendance/holidays/{id} */
    public function destroy(int $id): JsonResponse
    {
        Holiday::forSchool(app('current_school_id'))->findOrFail($id)->delete();

        return response()->json(['message' => 'Holiday removed.']);
    }
}
