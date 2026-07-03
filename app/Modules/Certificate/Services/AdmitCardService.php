<?php

namespace App\Modules\Certificate\Services;

use App\Models\User;
use App\Modules\Certificate\Models\AdmitCard;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSeating;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Services\PdfRenderingService;
use Illuminate\Support\Facades\DB;

/**
 * Admit Card content is a structured schedule/seating table, not free-form
 * prose — unlike Transfer Certificate / Testimonial it has no per-school
 * HTML template with placeholders, just a fixed layout built here.
 */
class AdmitCardService
{
    public function __construct(
        private readonly PdfRenderingService $pdf,
    ) {}

    public function generate(int $schoolId, Student $student, Exam $exam, User $generatedBy): AdmitCard
    {
        $school = School::findOrFail($schoolId);
        $html   = $this->buildHtml($school, $student, $exam);

        $path = $this->pdf->generateAndStore(
            $html,
            "certificates/{$schoolId}/admit-cards/{$student->id}-{$exam->id}.pdf",
        );

        return DB::transaction(function () use ($schoolId, $student, $exam, $generatedBy, $path): AdmitCard {
            return AdmitCard::updateOrCreate(
                [
                    'school_id'  => $schoolId,
                    'student_id' => $student->id,
                    'exam_id'    => $exam->id,
                ],
                [
                    'file_path'    => $path,
                    'generated_at' => now(),
                    'generated_by' => $generatedBy->id,
                ],
            );
        });
    }

    private function buildHtml(School $school, Student $student, Exam $exam): string
    {
        $subjects = $exam->subjects()->with('subjectRelation.subject')->get();

        $seating = ExamSeating::where('school_id', $school->id)
            ->where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->with('hallSeat.hall')
            ->first();

        $hallName = $seating?->hallSeat?->hall?->name ?? 'To be announced';
        $seatLabel = $seating?->hallSeat?->label ?? 'To be announced';
        $examRoll = $seating?->exam_roll ?? '-';

        $rows = '';
        foreach ($subjects as $subject) {
            $name = $subject->subjectRelation?->subject?->name ?? 'Subject';
            $date = $subject->exam_date?->format('d M Y') ?? 'TBA';
            $start = $subject->start_time ?? '-';
            $end = $subject->end_time ?? '-';
            $rows .= "<tr><td>{$name}</td><td>{$date}</td><td>{$start} - {$end}</td></tr>";
        }

        $studentName = e($student->name);
        $admissionNumber = e($student->admission_number);
        $schoolName = e($school->name);
        $examTitle = e($exam->title);

        return <<<HTML
            <html>
            <head><style>
                body { font-family: sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
                h2 { margin-bottom: 0; }
            </style></head>
            <body>
                <h2>{$schoolName}</h2>
                <h3>Admit Card — {$examTitle}</h3>
                <p><strong>Student:</strong> {$studentName} ({$admissionNumber})</p>
                <p><strong>Exam Roll:</strong> {$examRoll}</p>
                <p><strong>Hall:</strong> {$hallName} &nbsp; <strong>Seat:</strong> {$seatLabel}</p>
                <table>
                    <thead><tr><th>Subject</th><th>Date</th><th>Time</th></tr></thead>
                    <tbody>{$rows}</tbody>
                </table>
            </body>
            </html>
            HTML;
    }
}
