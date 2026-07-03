<?php

namespace App\Modules\Certificate\Services;

use App\Models\User;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Certificate\Repositories\TestimonialRepository;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Services\PdfRenderingService;
use Illuminate\Support\Facades\DB;

/**
 * Testimonial = conduct remark (always) + an optional academic summary pulled
 * from Mark's ExamResult (when exam_id is given) + an optional attendance %
 * (when an explicit date range is given — never inferred from academic_years,
 * which has no start/end dates; guessing calendar bounds would bake in a
 * BD-style assumption the Global Product Rules warn against).
 */
class TestimonialService
{
    public function __construct(
        private readonly TestimonialRepository $repository,
        private readonly PdfRenderingService $pdf,
        private readonly AttendanceService $attendance,
    ) {}

    /**
     * @param  array{conduct_remark: string, exam_id?: int|null, template_id?: int|null, attendance_from?: string|null, attendance_to?: string|null}  $data
     */
    public function generate(int $schoolId, Student $student, array $data, User $issuedBy): Testimonial
    {
        $template = ! empty($data['template_id'])
            ? TestimonialTemplate::forSchool($schoolId)->findOrFail($data['template_id'])
            : TestimonialTemplate::forSchool($schoolId)->default()->first();

        return Testimonial::create([
            'school_id'          => $schoolId,
            'student_id'         => $student->id,
            'template_id'        => $template?->id,
            'exam_id'            => $data['exam_id'] ?? null,
            'testimonial_number' => $this->generateNumber($schoolId),
            'issued_date'        => now()->toDateString(),
            'issued_by'          => $issuedBy->id,
            'conduct_remark'     => $data['conduct_remark'],
            'attendance_from'    => $data['attendance_from'] ?? null,
            'attendance_to'      => $data['attendance_to'] ?? null,
        ]);
    }

    /** Render template placeholders for preview. */
    public function render(Testimonial $testimonial): string
    {
        $testimonial->load(['student.currentAcademic.schoolClass', 'template', 'exam']);

        if (! $testimonial->template) {
            return "Testimonial #{$testimonial->testimonial_number} — no template assigned.";
        }

        $school   = School::findOrFail($testimonial->school_id);
        $academic = $testimonial->student->currentAcademic;
        $result   = $testimonial->exam_id
            ? ExamResult::forSchool($testimonial->school_id)
                ->where('student_id', $testimonial->student_id)
                ->where('exam_id', $testimonial->exam_id)
                ->first()
            : null;

        $attendancePercentage = 'N/A';
        if ($testimonial->attendance_from && $testimonial->attendance_to) {
            $summary = $this->attendance->studentSummary(
                $testimonial->school_id,
                $testimonial->student_id,
                $testimonial->attendance_from->toDateString(),
                $testimonial->attendance_to->toDateString(),
            );
            $attendancePercentage = $summary['percentage'] . '%';
        }

        $replacements = [
            '{{student_name}}'         => $testimonial->student->name,
            '{{admission_number}}'     => $testimonial->student->admission_number,
            '{{class}}'                => $academic?->schoolClass?->name ?? '-',
            '{{conduct_remark}}'       => $testimonial->conduct_remark,
            '{{grade}}'                => $result?->grade ?? 'N/A',
            '{{gpa}}'                  => $result?->gpa ?? 'N/A',
            '{{percentage}}'           => $result?->percentage !== null ? "{$result->percentage}%" : 'N/A',
            '{{attendance_percentage}}' => $attendancePercentage,
            '{{issued_date}}'          => $testimonial->issued_date->format('d M Y'),
            '{{school_name}}'          => $school->name,
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $testimonial->template->template_body,
        );
    }

    /** Generate the PDF and mark the testimonial officially issued. */
    public function issue(Testimonial $testimonial): Testimonial
    {
        $html = $this->render($testimonial);

        return DB::transaction(function () use ($testimonial, $html): Testimonial {
            $path = $this->pdf->generateAndStore(
                $html,
                "certificates/{$testimonial->school_id}/testimonials/{$testimonial->id}.pdf",
            );

            $testimonial->update(['status' => 'issued', 'file_path' => $path]);

            return $testimonial->fresh();
        });
    }

    private function generateNumber(int $schoolId): string
    {
        $year  = now()->format('Y');
        $count = $this->repository->countForYear($schoolId, (int) $year) + 1;

        return 'TST/' . $year . '/' . str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }
}
