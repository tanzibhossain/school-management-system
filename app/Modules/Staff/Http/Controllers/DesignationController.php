<?php

namespace App\Modules\Staff\Http\Controllers;

use App\Modules\Staff\Http\Requests\StoreDesignationRequest;
use App\Modules\Staff\Http\Resources\DesignationResource;
use App\Modules\Staff\Models\Designation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class DesignationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $designations = Designation::where('school_id', app('current_school_id'))
            ->orderBy('name')
            ->get();

        return DesignationResource::collection($designations);
    }

    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $designation = Designation::create([
            'school_id' => app('current_school_id'),
            'name' => $request->validated()['name'],
        ]);

        return (new DesignationResource($designation))->response()->setStatusCode(201);
    }

    public function update(StoreDesignationRequest $request, int $id): DesignationResource
    {
        $designation = Designation::where('school_id', app('current_school_id'))->findOrFail($id);
        $designation->update($request->validated());

        return new DesignationResource($designation->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        Designation::where('school_id', app('current_school_id'))->findOrFail($id)->delete();

        return response()->json(['message' => 'Designation deleted.']);
    }
}
