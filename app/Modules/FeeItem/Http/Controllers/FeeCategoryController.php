<?php

namespace App\Modules\FeeItem\Http\Controllers;

use App\Modules\FeeItem\Http\Requests\StoreFeeCategoryRequest;
use App\Modules\FeeItem\Http\Resources\FeeCategoryResource;
use App\Modules\FeeItem\Models\FeeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use App\Support\CacheTags;

class FeeCategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $categories = FeeCategory::where('school_id', app('current_school_id'))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get();

        return FeeCategoryResource::collection($categories);
    }

    public function store(StoreFeeCategoryRequest $request): JsonResponse
    {
        $category = FeeCategory::create(array_merge(
            $request->validated(),
            ['school_id' => app('current_school_id')],
        ));

        CacheTags::flush(['fee-item']);

        return (new FeeCategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(StoreFeeCategoryRequest $request, int $id): FeeCategoryResource
    {
        $category = FeeCategory::where('school_id', app('current_school_id'))->findOrFail($id);
        $category->update($request->validated());
        CacheTags::flush(['fee-item']);

        return new FeeCategoryResource($category->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        $category = FeeCategory::where('school_id', app('current_school_id'))->findOrFail($id);
        $category->delete();
        CacheTags::flush(['fee-item']);

        return response()->json(['message' => 'Category deleted.']);
    }
}
