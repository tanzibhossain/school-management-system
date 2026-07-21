<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Examination\Services\ExaminationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class ExamController extends Controller
{
    public function __construct(private readonly ExaminationService $exams) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $exams = Exam::where('school_id', $schoolId)
            ->with(['examType:id,name', 'schoolClass:id,name'])
            ->withCount('subjects')
            ->orderByDesc('id')
            ->get();

        return view('admin.academics.exams.index', [
            'exams' => $exams,
            'types' => ExamType::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'years' => AcademicYear::where('school_id', $schoolId)->where('is_trash', false)->orderByDesc('year')->get(['id', 'year', 'is_current']),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'sections' => Section::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'class_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'exam_type_id' => ['required', 'integer', "exists:exam_types,id,school_id,{$schoolId}"],
            'academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$schoolId}"],
            'class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'section_id' => ['nullable', 'integer', "exists:sections,id,school_id,{$schoolId}"],
            'title' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'seating_strategy' => ['nullable', 'in:sequential,interleave_group,interleave_section,anti_adjacency'],
        ]);
        $data['seating_strategy'] = $data['seating_strategy'] ?? 'sequential';

        $exam = $this->exams->create($schoolId, $data);

        return redirect()->route('admin.exams.show', $exam->id)->with('status', 'Exam created.');
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)
            ->with(['examType:id,name', 'schoolClass:id,name', 'subjects.subjectRelation.subject:id,name'])
            ->findOrFail($id);

        $subjectRelations = SubjectRelation::where('school_id', $schoolId)
            ->where('class_id', $exam->class_id)
            ->with('subject:id,name')
            ->get();

        return view('admin.academics.exams.show', compact('exam', 'subjectRelations'));
    }

    public function publish(int $id): RedirectResponse
    {
        return $this->transition($id, fn (Exam $e) => $this->exams->publish($e), 'Exam published.');
    }

    public function complete(int $id): RedirectResponse
    {
        return $this->transition($id, fn (Exam $e) => $this->exams->complete($e), 'Exam marked completed.');
    }

    public function addSubject(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'subject_relation_id' => ['required', 'integer', "exists:subject_relations,id,school_id,{$schoolId}"],
            'exam_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'full_marks' => ['required', 'numeric', 'min:1'],
            'pass_marks' => ['required', 'numeric', 'min:0', 'lte:full_marks'],
        ], [], ['subject_relation_id' => 'subject']);

        try {
            $this->exams->addSubject($exam, $data);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Subject added to exam.');
    }

    public function removeSubject(int $id, int $subjectId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $subject = ExamSubject::where('school_id', $schoolId)->where('exam_id', $id)->findOrFail($subjectId);

        try {
            $this->exams->removeSubject($subject);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Subject removed.');
    }

    private function transition(int $id, callable $action, string $message): RedirectResponse
    {
        $exam = Exam::where('school_id', app('current_school_id'))->findOrFail($id);

        try {
            $action($exam);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $message);
    }
}
