<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreClassRoutineRequest;
use App\Modules\Academic\Http\Requests\UpdateClassRoutineRequest;
use App\Modules\Academic\Http\Resources\ClassRoutineResource;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Repositories\AcademicRepository;
use App\Modules\Academic\Services\RoutineSchedulingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ClassRoutineController extends Controller
{
    public function __construct(
        private readonly AcademicRepository $repository,
        private readonly RoutineSchedulingService $scheduling,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $classId = (int) $request->query('class_id', 0);
        $sectionId = (int) $request->query('section_id', 0);

        $routines = $this->repository->getRoutineForClass($schoolId, $classId, $sectionId);

        return ClassRoutineResource::collection($routines);
    }

    public function store(StoreClassRoutineRequest $request): ClassRoutineResource|JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        if ($this->scheduling->hasConflict($schoolId, $data['room_id'], $data['section_id'], $data['period_id'], $data['day_of_week'])) {
            return response()->json(['message' => 'Time slot conflict: room or section already booked.'], 422);
        }

        $routine = ClassRoutine::create(array_merge($data, ['school_id' => $schoolId]));

        return new ClassRoutineResource($routine->load(['subject', 'room', 'period', 'shift']));
    }

    public function show(int $id): ClassRoutineResource
    {
        $routine = ClassRoutine::where('school_id', app('current_school_id'))
            ->with(['subject', 'room', 'period', 'shift'])
            ->findOrFail($id);

        return new ClassRoutineResource($routine);
    }

    public function update(UpdateClassRoutineRequest $request, int $id): ClassRoutineResource|JsonResponse
    {
        $schoolId = app('current_school_id');
        $routine = ClassRoutine::where('school_id', $schoolId)->findOrFail($id);
        $data = $request->validated();

        $roomId = $data['room_id'] ?? $routine->room_id;
        $sectionId = $data['section_id'] ?? $routine->section_id;
        $periodId = $data['period_id'] ?? $routine->period_id;
        $day = $data['day_of_week'] ?? $routine->day_of_week;

        if ($this->scheduling->hasConflict($schoolId, $roomId, $sectionId, $periodId, $day, $id)) {
            return response()->json(['message' => 'Time slot conflict: room or section already booked.'], 422);
        }

        $routine->update($data);

        return new ClassRoutineResource($routine->fresh()->load(['subject', 'room', 'period', 'shift']));
    }

    public function destroy(int $id): JsonResponse
    {
        ClassRoutine::where('school_id', app('current_school_id'))->findOrFail($id)->delete();

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
