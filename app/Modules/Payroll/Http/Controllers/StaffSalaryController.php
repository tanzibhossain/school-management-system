<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Modules\Payroll\Http\Requests\SetStaffSalaryValuesRequest;
use App\Modules\Payroll\Http\Resources\StaffSalaryValueResource;
use App\Modules\Payroll\Services\StaffSalaryValueService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class StaffSalaryController extends Controller
{
    public function __construct(private readonly StaffSalaryValueService $service) {}

    public function show(int $staffId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        Staff::where('school_id', $schoolId)->findOrFail($staffId);

        return StaffSalaryValueResource::collection($this->service->breakdown($schoolId, $staffId));
    }

    public function store(SetStaffSalaryValuesRequest $request, int $staffId): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        Staff::where('school_id', $schoolId)->findOrFail($staffId);

        $this->service->setValues($schoolId, $staffId, $request->validated('values'));

        return StaffSalaryValueResource::collection($this->service->breakdown($schoolId, $staffId));
    }
}
