<?php

namespace App\Modules\Payroll\Services;

use App\Models\User;
use App\Modules\Payroll\Models\SalaryCertificateRequest;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Services\PdfRenderingService;
use Illuminate\Support\Carbon;

class SalaryCertificateService
{
    public function __construct(
        private readonly PdfRenderingService $pdf,
        private readonly StaffSalaryValueService $salaryValues,
    ) {}

    public function request(int $schoolId, int $staffId, string $purpose): SalaryCertificateRequest
    {
        return SalaryCertificateRequest::create([
            'school_id' => $schoolId,
            'staff_id' => $staffId,
            'purpose' => $purpose,
            'requested_at' => now(),
        ]);
    }

    public function generate(int $schoolId, SalaryCertificateRequest $request, User $generatedBy): SalaryCertificateRequest
    {
        $staff = Staff::where('school_id', $schoolId)->with(['designation', 'department'])->findOrFail($request->staff_id);
        $school = School::findOrFail($schoolId);
        $gross = $this->salaryValues->calculateGrossAndNet($schoolId, $staff->id)['gross'];

        $html = $this->buildHtml($school, $staff, $request, $gross);
        $path = $this->pdf->generateAndStore(
            $html,
            "{$schoolId}/payroll/certificates/{$staff->id}_{$request->id}.pdf",
        );

        $request->update([
            'status' => 'generated',
            'certificate_path' => $path,
            'generated_at' => now(),
            'generated_by' => $generatedBy->id,
        ]);

        return $request->fresh();
    }

    private function buildHtml(School $school, Staff $staff, SalaryCertificateRequest $request, float $gross): string
    {
        $joined = $staff->joining_date ? Carbon::parse($staff->joining_date) : null;
        $duration = $joined ? $joined->diffForHumans(now(), true) : 'N/A';

        $schoolName = e($school->name);
        $staffName = e($staff->name);
        $designation = e($staff->designation?->name ?? '-');
        $department = e($staff->department?->name ?? '-');
        $purpose = e($request->purpose);
        $currency = e($school->currency);
        $grossFormatted = number_format($gross, 2);

        return <<<HTML
            <html>
            <head><style>
                body { font-family: sans-serif; }
                h2 { margin-bottom: 0; }
            </style></head>
            <body>
                <h2>{$schoolName}</h2>
                <h3>Salary Certificate</h3>
                <p>This is to certify that <strong>{$staffName}</strong>, working as
                <strong>{$designation}</strong> in the <strong>{$department}</strong> department,
                has been employed at this institution for approximately <strong>{$duration}</strong>.</p>
                <p>Current gross monthly salary: <strong>{$currency} {$grossFormatted}</strong>.</p>
                <p>Purpose of this certificate: {$purpose}.</p>
            </body>
            </html>
            HTML;
    }
}
