<?php

namespace App\Modules\Academic\Http\Controllers;

use App\Modules\Academic\Http\Resources\AcademicGroupResource;
use App\Modules\Academic\Http\Resources\AcademicShiftResource;
use App\Modules\Academic\Http\Resources\AcademicVersionResource;
use App\Modules\Academic\Http\Resources\AcademicYearResource;
use App\Modules\Academic\Http\Resources\SchoolClassResource;
use App\Modules\Academic\Http\Resources\StudentTypeResource;
use App\Modules\Academic\Http\Resources\TransportResource;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AcademicPublicController extends Controller
{
    public function __construct(private readonly AcademicRepository $repository) {}

    public function years(): AnonymousResourceCollection
    {
        return AcademicYearResource::collection($this->repository->getYears(app('current_school_id')));
    }

    public function classes(): AnonymousResourceCollection
    {
        return SchoolClassResource::collection($this->repository->getActiveClasses(app('current_school_id')));
    }

    public function shifts(): AnonymousResourceCollection
    {
        return AcademicShiftResource::collection($this->repository->getActiveShifts(app('current_school_id')));
    }

    public function versions(): AnonymousResourceCollection
    {
        return AcademicVersionResource::collection($this->repository->getActiveVersions(app('current_school_id')));
    }

    public function groups(): AnonymousResourceCollection
    {
        return AcademicGroupResource::collection($this->repository->getActiveGroups(app('current_school_id')));
    }

    public function transports(): AnonymousResourceCollection
    {
        return TransportResource::collection($this->repository->getActiveTransports(app('current_school_id')));
    }

    public function studentTypes(): AnonymousResourceCollection
    {
        return StudentTypeResource::collection($this->repository->getActiveStudentTypes(app('current_school_id')));
    }

    /** All reference dropdown data in one request. */
    public function dropdowns(): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $this->repository->getDropdownData($schoolId);

        return response()->json([
            'data' => [
                'academic_year' => $data['current_year'] ? new AcademicYearResource($data['current_year']) : null,
                'classes' => SchoolClassResource::collection($data['classes']),
                'shifts' => AcademicShiftResource::collection($data['shifts']),
                'versions' => AcademicVersionResource::collection($data['versions']),
                'groups' => AcademicGroupResource::collection($data['groups']),
                'transports' => TransportResource::collection($data['transports']),
                'student_types' => StudentTypeResource::collection($data['student_types']),
            ],
        ]);
    }
}
