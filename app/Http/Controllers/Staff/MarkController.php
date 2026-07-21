<?php

namespace App\Http\Controllers\Staff;

use App\Modules\Examination\Models\Exam;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Services\MarkEntryService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Teacher marks entry. A teacher enters marks for divisions of their assigned
 * subject; the MarkEntryService enforces subject ownership. Result calculation
 * and locking stay with admins/moderators.
 */
class MarkController extends Controller
{
    public function __construct(private readonly MarkEntryService $entry) {}

    public function index(): View
    {
        [$sid, $staff] = $this->context();
        $subjectId = $staff?->subject_id;

        $grouped = collect();
        $exams = collect();
        if ($subjectId) {
            $divisions = MarkDivision::forSchool($sid)
                ->whereHas('examSubject.subjectRelation', fn ($q) => $q->where('subject_id', $subjectId))
                ->with(['examSubject.subjectRelation.subject:id,name'])
                ->orderBy('display_order')->get();

            $grouped = $divisions->groupBy('exam_id');
            $exams = Exam::where('school_id', $sid)->whereIn('id', $grouped->keys())
                ->with(['schoolClass:id,name', 'examType:id,name'])->get()->keyBy('id');
        }

        return view('staff.marks.index', [
            'staff' => $staff,
            'grouped' => $grouped,
            'exams' => $exams,
        ]);
    }

    public function entry(int $examId, int $divisionId): View
    {
        [$sid, $staff] = $this->context();

        $exam = Exam::where('school_id', $sid)->findOrFail($examId);
        $division = MarkDivision::forSchool($sid)->where('exam_id', $examId)
            ->with('examSubject.subjectRelation.subject:id,name')->findOrFail($divisionId);

        $this->assertOwnsSubject($staff, $division);

        $academics = StudentAcademic::where('school_id', $sid)
            ->where('class_id', $exam->class_id)->where('academic_year_id', $exam->academic_year_id)
            ->where('is_current', true)
            ->whereHas('student', fn ($q) => $q->where('status', 'active')->where('is_trash', false))
            ->with('student:id,name,student_id')->get();

        $marks = Mark::forSchool($sid)->where('mark_division_id', $division->id)->get()->keyBy('student_id');

        $roster = $academics->filter(fn ($a) => $a->student !== null)->map(function ($a) use ($marks) {
            $m = $marks->get($a->student_id);

            return (object) [
                'student_id' => $a->student_id,
                'name' => $a->student->name,
                'code' => $a->student->student_id,
                'obtained' => $m?->marks_obtained,
                'is_absent' => (bool) ($m?->is_absent ?? false),
                'locked' => $m?->isLocked() ?? false,
            ];
        })->sortBy('name')->values();

        return view('staff.marks.entry', compact('exam', 'division', 'roster'));
    }

    public function saveEntry(Request $request, int $examId, int $divisionId): RedirectResponse
    {
        [$sid, $staff] = $this->context();
        $division = MarkDivision::forSchool($sid)->where('exam_id', $examId)
            ->with('examSubject.subjectRelation')->findOrFail($divisionId);

        $this->assertOwnsSubject($staff, $division);

        $request->validate([
            'marks' => ['array'],
            'marks.*' => ['nullable', 'numeric', 'min:0'],
            'absent' => ['array'],
        ]);

        $marks = $request->input('marks', []);
        $absentKeys = array_keys($request->input('absent', []));
        $studentIds = array_unique(array_map('intval', array_merge(array_keys($marks), $absentKeys)));

        $entries = [];
        foreach ($studentIds as $studentId) {
            $isAbsent = in_array((string) $studentId, array_map('strval', $absentKeys), true);
            $value = $marks[$studentId] ?? null;
            if (! $isAbsent && ($value === null || $value === '')) {
                continue;
            }
            $entries[] = ['student_id' => $studentId, 'marks_obtained' => $isAbsent ? null : $value, 'is_absent' => $isAbsent];
        }

        if ($entries === []) {
            return back()->with('error', __('Nothing to save — enter marks or mark students absent.'));
        }

        try {
            $result = $this->entry->bulkEnter($sid, $division->id, $entries, $request->user());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', "Marks saved — {$result['created']} new, {$result['updated']} updated.");
    }

    private function assertOwnsSubject(?Staff $staff, MarkDivision $division): void
    {
        $subjectId = $division->examSubject?->subjectRelation?->subject_id;
        if (! $staff || ! $subjectId || (int) $staff->subject_id !== (int) $subjectId) {
            throw new AccessDeniedHttpException('You can only enter marks for your assigned subject.');
        }
    }

    /** @return array{0:int,1:?Staff} */
    private function context(): array
    {
        $sid = app('current_school_id');

        return [$sid, Staff::where('school_id', $sid)->where('user_id', auth()->id())->first()];
    }
}
