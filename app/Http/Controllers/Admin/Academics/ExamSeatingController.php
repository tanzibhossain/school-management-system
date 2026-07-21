<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamSeating;
use App\Modules\Examination\Services\SeatingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;

class ExamSeatingController extends Controller
{
    public function __construct(private readonly SeatingService $seating) {}

    public function index(int $examId): View
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->with('schoolClass:id,name')->findOrFail($examId);

        $seating = ExamSeating::where('exam_id', $exam->id)
            ->with(['student:id,name,student_id', 'hallSeat:id,hall_id,label,row,side,position', 'hallSeat.hall:id,name'])
            ->get()
            ->sortBy(fn ($s) => [$s->hallSeat?->row, $s->hallSeat?->side, $s->hallSeat?->position])
            ->values();

        return view('admin.academics.exam-seating.index', [
            'exam' => $exam,
            'seating' => $seating,
            'halls' => ExamHall::where('school_id', $schoolId)
                ->withCount(['seats as available_count' => fn ($q) => $q->where('is_available', true)])
                ->orderBy('name')->get(),
        ]);
    }

    public function assign(Request $request, int $examId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->findOrFail($examId);

        $data = $request->validate([
            'hall_id' => ['required', 'integer', "exists:exam_halls,id,school_id,{$schoolId}"],
            'strategy' => ['nullable', 'in:sequential,interleave_group,interleave_section,anti_adjacency'],
            'blank_every' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        try {
            $count = $this->seating->assign($exam, $data['hall_id'], $data['strategy'] ?? null, $data['blank_every'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($count === 0) {
            return back()->with('error', __('No students were seated — check the hall has enough available seats and the class has enrolled students.'));
        }

        return back()->with('status', "Seated {$count} students.");
    }

    public function clear(int $examId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $exam = Exam::where('school_id', $schoolId)->findOrFail($examId);
        $this->seating->clear($exam);

        return back()->with('status', __('Seating cleared.'));
    }
}
