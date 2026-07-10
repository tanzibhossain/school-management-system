<?php

namespace App\Http\Controllers\Admin\Comms;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Report\Services\ReportPdfBuilder;
use App\Modules\Report\Services\ReportService;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Services\PdfRenderingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly ReportPdfBuilder $pdfBuilder,
        private readonly PdfRenderingService $pdf,
    ) {}

    public function feeCollection(Request $request): View|Response
    {
        $schoolId = app('current_school_id');

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date', 'after_or_equal:date_from'],
            'format'    => ['nullable', 'in:pdf'],
        ]);
        $from = $filters['date_from'] ?? now()->startOfMonth()->format('Y-m-d');
        $to = $filters['date_to'] ?? now()->format('Y-m-d');

        $data = null;
        if ($request->hasAny(['date_from', 'date_to']) || $request->boolean('run')) {
            $data = $this->reports->feeCollection($schoolId, ['date_from' => $from, 'date_to' => $to]);

            if (($filters['format'] ?? null) === 'pdf') {
                $html = $this->pdfBuilder->feeCollectionHtml($data, School::findOrFail($schoolId), $from, $to);

                return $this->stream($html, 'fee-collection.pdf');
            }
        }

        return view('admin.comms.reports.fee-collection', compact('data', 'from', 'to'));
    }

    public function outstandingDues(Request $request): View|Response
    {
        $schoolId = app('current_school_id');

        $filters = $request->validate([
            'class_id' => ['nullable', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'format'   => ['nullable', 'in:pdf'],
        ]);

        $data = $this->reports->outstandingDues($schoolId, ['class_id' => $filters['class_id'] ?? null]);

        if (($filters['format'] ?? null) === 'pdf') {
            $html = $this->pdfBuilder->outstandingDuesHtml($data, School::findOrFail($schoolId));

            return $this->stream($html, 'outstanding-dues.pdf');
        }

        return view('admin.comms.reports.outstanding-dues', [
            'data'    => $data,
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'classId' => $filters['class_id'] ?? null,
        ]);
    }

    public function studentLedger(Request $request): View|Response
    {
        $schoolId = app('current_school_id');

        $filters = $request->validate([
            'student_id' => ['nullable', 'integer', "exists:students,id,school_id,{$schoolId}"],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date', 'after_or_equal:date_from'],
            'format'     => ['nullable', 'in:pdf'],
        ]);

        $data = null;
        $student = null;
        if (! empty($filters['student_id'])) {
            $student = Student::where('school_id', $schoolId)->findOrFail($filters['student_id']);
            $data = $this->reports->studentLedger($schoolId, $student->id, [
                'date_from' => $filters['date_from'] ?? null,
                'date_to'   => $filters['date_to'] ?? null,
            ]);

            if (($filters['format'] ?? null) === 'pdf') {
                $html = $this->pdfBuilder->studentLedgerHtml($data, School::findOrFail($schoolId), $student->name);

                return $this->stream($html, "student-{$student->id}-ledger.pdf");
            }
        }

        return view('admin.comms.reports.student-ledger', [
            'data'     => $data,
            'student'  => $student,
            'students' => Student::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'student_id']),
            'filters'  => $filters,
        ]);
    }

    private function stream(string $html, string $filename): Response
    {
        $bytes = $this->pdf->renderToPdf($html);

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
