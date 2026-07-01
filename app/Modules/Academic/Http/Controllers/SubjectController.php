<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreSubjectRequest;
use App\Modules\Academic\Http\Requests\SyncSubjectRelationsRequest;
use App\Modules\Academic\Http\Requests\UpdateSubjectRequest;
use App\Modules\Academic\Http\Resources\SubjectRelationResource;
use App\Modules\Academic\Http\Resources\SubjectResource;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Repositories\AcademicRepository;
use App\Modules\Academic\Services\AcademicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SubjectController extends Controller
{
    public function __construct(
        private readonly AcademicRepository $repository,
        private readonly AcademicService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = Subject::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return SubjectResource::collection($items);
    }

    public function store(StoreSubjectRequest $request): SubjectResource
    {
        $item = Subject::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new SubjectResource($item);
    }

    public function show(int $id): SubjectResource
    {
        $item = Subject::where('school_id', app('current_school_id'))->findOrFail($id);

        return new SubjectResource($item);
    }

    public function update(UpdateSubjectRequest $request, int $id): SubjectResource
    {
        $item = Subject::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new SubjectResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = Subject::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }

    /**
     * Sync which subjects are assigned to a given class (by class_id).
     */
    public function syncRelations(SyncSubjectRelationsRequest $request, int $classId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $this->service->syncSubjectRelations($schoolId, $classId, $request->validated('relations'));
        $relations = $this->repository->getSubjectsForClass($schoolId, $classId);

        return SubjectRelationResource::collection($relations);
    }

    /**
     * Get subjects assigned to a class.
     */
    public function forClass(int $classId): AnonymousResourceCollection
    {
        $relations = $this->repository->getSubjectsForClass(app('current_school_id'), $classId);

        return SubjectRelationResource::collection($relations);
    }
}
