<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Requests\StoreSectionRequest;
use App\Modules\Academic\Http\Requests\UpdateSectionRequest;
use App\Modules\Academic\Http\Resources\SectionResource;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class SectionController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $items = Section::where('school_id', $schoolId)->active()->orderBy('name')->get();

        return SectionResource::collection($items);
    }

    public function store(StoreSectionRequest $request): SectionResource
    {
        $item = Section::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return new SectionResource($item);
    }

    public function show(int $id): SectionResource
    {
        $item = Section::where('school_id', app('current_school_id'))->findOrFail($id);

        return new SectionResource($item);
    }

    public function update(UpdateSectionRequest $request, int $id): SectionResource
    {
        $item = Section::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update($request->validated());

        return new SectionResource($item->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $item = Section::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
