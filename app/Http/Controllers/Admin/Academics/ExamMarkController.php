<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Services\MarkEntryService;
use App\Modules\Mark\Services\ResultCalculationService;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Sanctum\TransientToken;

class ExamMarkController extends Controller
{
    public function __construct(
        private readonly MarkEntryService $entry,
        private readonly ResultCalculationService $results,
    ) {}

    public function index(int $examId): View
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)
            ->with(['examType:id,name', 'schoolClass:id,name', 'subjects.subjectRelation.subject:id,name'])
            ->findOrFail($examId);

        $divisions = MarkDivision::forSchool($schoolId)->where('exam_id', $examId)->get()->groupBy('exam_subject_id');

        $resultCount = ExamResult::where('school_id', $schoolId)->where('exam_id', $examId)->count();
        $locked = ExamResult::where('school_id', $schoolId)->where('exam_id', $examId)->where('is_locked', true)->exists();

        return view('admin.academics.exam-marks.index', compact('exam', 'divisions', 'resultCount', 'locked'));
    }

    public function storeDivision(Request $request, int $examId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->findOrFail($examId);

        $data = $request->validate([
            'exam_subject_id' => ['required', 'integer'],
            'name'            => ['required', 'string', 'max:50'],
            'max_marks'       => ['required', 'numeric', 'min:1'],
            'pass_mark'       => ['nullable', 'numeric', 'min:0', 'lte:max_marks'],
        ]);

        $examSubject = ExamSubject::where('school_id', $schoolId)->where('exam_id', $exam->id)->findOrFail($data['exam_subject_id']);

        $order = MarkDivision::forSchool($schoolId)->where('exam_subject_id', $examSubject->id)->max('display_order');

        MarkDivision::create([
            'school_id'       => $schoolId,
            'exam_id'         => $exam->id,
            'exam_subject_id' => $examSubject->id,
            'name'            => $data['name'],
            'max_marks'       => $data['max_marks'],
            'pass_mark'       => $data['pass_mark'] ?? null,
            'display_order'   => (int) $order + 1,
        ]);

        return back()->with('status', 'Division added.');
    }

    public function destroyDivision(int $examId, int $divisionId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $division = MarkDivision::forSchool($schoolId)->where('exam_id', $examId)->findOrFail($divisionId);

        if (Mark::forSchool($schoolId)->where('mark_division_id', $division->id)->exists()) {
            return back()->with('error', 'Cannot delete a division that already has marks entered.');
        }

        $division->delete();

        return back()->with('status', 'Division removed.');
    }

    public function entry(int $examId, int $divisionId): View
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->findOrFail($examId);
        $division = MarkDivision::forSchool($schoolId)->where('exam_id', $examId)
            ->with('examSubject.subjectRelation.subject:id,name')->findOrFail($divisionId);

        $academics = StudentAcademic::where('school_id', $schoolId)
            ->where('class_id', $exam->class_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->where('is_current', true)
            ->whereHas('student', fn ($q) => $q->where('status', 'active')->where('is_trash', false))
            ->with('student:id,name,student_id')
            ->get();

        $marks = Mark::forSchool($schoolId)->where('mark_division_id', $division->id)->get()->keyBy('student_id');

        $roster = $academics->filter(fn ($a) => $a->student !== null)->map(function ($a) use ($marks) {
            $m = $marks->get($a->student_id);

            return (object) [
                'student_id' => $a->student_id,
                'name'       => $a->student->name,
                'code'       => $a->student->student_id,
                'obtained'   => $m?->marks_obtained,
                'is_absent'  => (bool) ($m?->is_absent ?? false),
                'locked'     => $m?->isLocked() ?? false,
            ];
        })->sortBy('name')->values();

        return view('admin.academics.exam-marks.entry', compact('exam', 'division', 'roster'));
    }

    public function saveEntry(Request $request, int $examId, int $divisionId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $division = MarkDivision::forSchool($schoolId)->where('exam_id', $examId)->findOrFail($divisionId);

        $validated = $request->validate([
            'marks'            => ['array'],
            'marks.*'          => ['nullable', 'numeric', 'min:0'],
            'absent'           => ['array'],
        ]);

        $marks = $request->input('marks', []);
        $absentKeys = array_keys($request->input('absent', []));

        // Union of students appearing in either input (absent inputs are disabled
        // in the grid, so absent-only students would otherwise be missed).
        $studentIds = array_unique(array_map('intval', array_merge(array_keys($marks), $absentKeys)));

        $entries = [];
        foreach ($studentIds as $studentId) {
            $isAbsent = in_array((string) $studentId, array_map('strval', $absentKeys), true);
            $value = $marks[$studentId] ?? null;

            if (! $isAbsent && ($value === null || $value === '')) {
                continue; // skip unentered students
            }
            $entries[] = [
                'student_id'     => $studentId,
                'marks_obtained' => $isAbsent ? null : $value,
                'is_absent'      => $isAbsent,
            ];
        }

        if ($entries === []) {
            return back()->with('error', 'Nothing to save — enter marks or mark students absent.');
        }

        $recorder = $request->user();
        $recorder->withAccessToken(new TransientToken());

        try {
            $result = $this->entry->bulkEnter($schoolId, $division->id, $entries, $recorder);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', "Marks saved — {$result['created']} new, {$result['updated']} updated.");
    }

    public function calculate(int $examId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        Exam::where('school_id', $schoolId)->findOrFail($examId);

        try {
            $count = $this->results->calculateForExam($schoolId, $examId);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.exam-marks.results', $examId)->with('status', "Calculated {$count} results.");
    }

    public function lock(int $examId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        Exam::where('school_id', $schoolId)->findOrFail($examId);

        try {
            $count = $this->results->lock($schoolId, $examId, (int) auth()->id());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', "Locked {$count} results.");
    }

    public function results(int $examId): View
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->with(['schoolClass:id,name', 'examType:id,name'])->findOrFail($examId);
        $rows = $this->results->tabulation($schoolId, $examId);
        $locked = $rows->isNotEmpty() && (bool) $rows->first()->is_locked;

        return view('admin.academics.exam-marks.results', compact('exam', 'rows', 'locked'));
    }
}
