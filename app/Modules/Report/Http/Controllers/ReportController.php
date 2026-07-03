<?php

namespace App\Modules\Report\Http\Controllers;

use App\Modules\Report\Http\Requests\FeeCollectionReportRequest;
use App\Modules\Report\Http\Requests\OutstandingDuesReportRequest;
use App\Modules\Report\Http\Requests\StudentLedgerReportRequest;
use App\Modules\Report\Http\Resources\FeeCollectionReportResource;
use App\Modules\Report\Http\Resources\OutstandingDuesReportResource;
use App\Modules\Report\Http\Resources\StudentLedgerReportResource;
use App\Modules\Report\Services\ReportPdfBuilder;
use App\Modules\Report\Services\ReportService;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Services\PdfRenderingService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $service,
        private readonly ReportPdfBuilder $pdfBuilder,
        private readonly PdfRenderingService $pdf,
    ) {}

    /** GET /v2/reports/fee-collection */
    public function feeCollection(FeeCollectionReportRequest $request): FeeCollectionReportResource|Response
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $result = $this->service->feeCollection($schoolId, $data);

        if (($data['format'] ?? 'json') === 'pdf') {
            $school = School::findOrFail($schoolId);
            $html = $this->pdfBuilder->feeCollectionHtml($result, $school, $data['date_from'], $data['date_to']);

            return $this->pdfResponse($html, 'fee-collection-report.pdf');
        }

        return new FeeCollectionReportResource($result);
    }

    /** GET /v2/reports/outstanding-dues */
    public function outstandingDues(OutstandingDuesReportRequest $request): OutstandingDuesReportResource|Response
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $result = $this->service->outstandingDues($schoolId, $data);

        if (($data['format'] ?? 'json') === 'pdf') {
            $school = School::findOrFail($schoolId);
            $html = $this->pdfBuilder->outstandingDuesHtml($result, $school);

            return $this->pdfResponse($html, 'outstanding-dues-report.pdf');
        }

        return new OutstandingDuesReportResource($result);
    }

    /** GET /v2/reports/students/{studentId}/ledger */
    public function studentLedger(StudentLedgerReportRequest $request, int $studentId): StudentLedgerReportResource|Response
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $student = Student::where('school_id', $schoolId)->findOrFail($studentId);
        $result = $this->service->studentLedger($schoolId, $studentId, $data);

        if (($data['format'] ?? 'json') === 'pdf') {
            $school = School::findOrFail($schoolId);
            $html = $this->pdfBuilder->studentLedgerHtml($result, $school, $student->name);

            return $this->pdfResponse($html, "student-{$studentId}-ledger.pdf");
        }

        return new StudentLedgerReportResource($result);
    }

    private function pdfResponse(string $html, string $filename): Response
    {
        $bytes = $this->pdf->renderToPdf($html);

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
