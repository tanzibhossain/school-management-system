<?php

namespace App\Modules\Student\Services;

use App\Models\User;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\TransferCertificate;
use App\Modules\Student\Models\TransferCertificateTemplate;
use App\Services\PdfRenderingService;
use Illuminate\Support\Facades\DB;

class TransferCertificateService
{
    public function __construct(
        private readonly PdfRenderingService $pdf,
    ) {}

    /**
     * Generate and persist a draft TC for a student. The PDF itself is only
     * rendered at issue() time (see below) — closes the gap this service used
     * to leave open (file_path was never written).
     */
    public function generate(
        Student $student,
        string $reason,
        ?int $templateId = null,
        ?User $issuedBy = null,
    ): TransferCertificate {
        return DB::transaction(function () use ($student, $reason, $templateId, $issuedBy): TransferCertificate {
            $template = $templateId
                ? TransferCertificateTemplate::where('school_id', $student->school_id)->findOrFail($templateId)
                : TransferCertificateTemplate::where('school_id', $student->school_id)->where('is_default', true)->first();

            $tcNumber = $this->generateTcNumber($student->school_id);

            return TransferCertificate::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'template_id' => $template?->id,
                'tc_number' => $tcNumber,
                'issued_date' => now()->toDateString(),
                'issued_by' => $issuedBy?->id,
                'reason' => $reason,
                'status' => 'draft',
            ]);
        });
    }

    /**
     * Render template placeholders for preview / PDF generation.
     */
    public function render(TransferCertificate $tc): string
    {
        $tc->load(['student.currentAcademic.schoolClass', 'template']);

        if (! $tc->template) {
            return "TC #{$tc->tc_number} — no template assigned.";
        }

        $academic = $tc->student->currentAcademic;

        $replacements = [
            '{{student_name}}' => $tc->student->name,
            '{{admission_number}}' => $tc->student->admission_number,
            '{{student_id}}' => $tc->student->student_id ?? '-',
            '{{class}}' => $academic?->schoolClass?->name ?? '-',
            '{{section}}' => $academic?->section?->name ?? '-',
            '{{tc_number}}' => $tc->tc_number,
            '{{issued_date}}' => $tc->issued_date->format('d M Y'),
            '{{reason}}' => ucfirst($tc->reason),
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $tc->template->template_body,
        );
    }

    /**
     * Generate the PDF (via the shared PdfRenderingService, same one Certificate's
     * Admit Card and Testimonial use) and mark the TC officially issued.
     */
    public function issue(TransferCertificate $tc): TransferCertificate
    {
        $html = $this->render($tc);
        $path = $this->pdf->generateAndStore(
            $html,
            "certificates/{$tc->school_id}/transfer-certificates/{$tc->id}.pdf",
        );

        $tc->update(['status' => 'issued', 'file_path' => $path]);

        return $tc->fresh();
    }

    private function generateTcNumber(int $schoolId): string
    {
        $year = now()->format('Y');
        $count = TransferCertificate::where('school_id', $schoolId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return 'TC/'.$year.'/'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
