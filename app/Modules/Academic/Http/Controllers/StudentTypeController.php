<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreStudentTypeRequest;
use App\Modules\Academic\Http\Requests\UpdateStudentTypeRequest;
use App\Modules\Academic\Http\Resources\StudentTypeResource;
use App\Modules\Academic\Models\StudentType;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StudentTypeController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = StudentType::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return StudentTypeResource::collection($items);
    }

    public function store(StoreStudentTypeRequest $request): StudentTypeResource
    {
        $item = StudentType::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new StudentTypeResource($item);
    }

    public function show(int $id): StudentTypeResource
    {
        $item = StudentType::where('school_id', app('current_school_id'))->findOrFail($id);

        return new StudentTypeResource($item);
    }

    public function update(UpdateStudentTypeRequest $request, int $id): StudentTypeResource
    {
        $item = StudentType::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new StudentTypeResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = StudentType::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
