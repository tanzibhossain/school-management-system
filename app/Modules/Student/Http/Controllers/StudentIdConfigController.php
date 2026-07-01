<?php

namespace App\Modules\Student\Http\Controllers;

use App\Modules\Student\Http\Requests\StoreStudentIdConfigRequest;
use App\Modules\Student\Models\StudentIdConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class StudentIdConfigController extends Controller
{
    public function show(): JsonResponse
    {
        $config = StudentIdConfig::where('school_id', app('current_school_id'))->first();

        return response()->json(['data' => $config]);
    }

    public function upsert(StoreStudentIdConfigRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');

        $config = StudentIdConfig::updateOrCreate(
            ['school_id' => $schoolId],
            $request->validated(),
        );

        return response()->json(['data' => $config]);
    }
}
