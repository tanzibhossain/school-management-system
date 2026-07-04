<?php

namespace App\Modules\Payroll\Http\Controllers;

use App\Modules\Payroll\Http\Requests\GeneratePayslipRequest;
use App\Modules\Payroll\Http\Resources\PayrollEntryResource;
use App\Modules\Payroll\Models\PayrollEntry;
use App\Modules\Payroll\Services\PayslipService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PayslipController extends Controller
{
    public function __construct(private readonly PayslipService $service) {}

    /** POST /v2/payroll/entries/{id}/payslip — admin/accountant only. */
    public function generate(GeneratePayslipRequest $request, int $id): PayrollEntryResource
    {
        $schoolId = app('current_school_id');
        $entry = PayrollEntry::forSchool($schoolId)->findOrFail($id);

        return new PayrollEntryResource($this->service->generate($schoolId, $entry));
    }

    /** GET /v2/payroll/staff/me/payslips — self-service, own record only. */
    public function myPayslips(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->where('user_id', $request->user()->id)->firstOrFail();

        $entries = PayrollEntry::forSchool($schoolId)
            ->where('staff_id', $staff->id)
            ->whereNotNull('payslip_path')
            ->with('run')
            ->orderByDesc('created_at')
            ->get();

        return PayrollEntryResource::collection($entries);
    }
}
