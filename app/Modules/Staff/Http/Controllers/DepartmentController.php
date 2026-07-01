<?php

namespace App\Modules\Staff\Http\Controllers;

use App\Modules\Staff\Http\Requests\StoreDepartmentRequest;
use App\Modules\Staff\Http\Resources\DepartmentResource;
use App\Modules\Staff\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class DepartmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $departments = Department::where('school_id', app('current_school_id'))
            ->orderBy('name')
            ->get();

        return DepartmentResource::collection($departments);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = Department::create([
            'school_id' => app('current_school_id'),
            'name'      => $request->validated()['name'],
        ]);

        return (new DepartmentResource($department))->response()->setStatusCode(201);
    }

    public function update(StoreDepartmentRequest $request, int $id): DepartmentResource
    {
        $department = Department::where('school_id', app('current_school_id'))->findOrFail($id);
        $department->update($request->validated());

        return new DepartmentResource($department->fresh());
    }

    public function destroy(int $id): JsonResponse
    {
        Department::where('school_id', app('current_school_id'))->findOrFail($id)->delete();

        return response()->json(['message' => 'Department deleted.']);
    }
}
