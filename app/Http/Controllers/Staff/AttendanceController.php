<?php

namespace App\Http\Controllers\Staff;

use App\Modules\Academic\Models\Section;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Teacher attendance register. A teacher records the daily register for the
 * sections they lead; the AttendanceService enforces class-teacher ownership.
 */
class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        [$sid, $staff] = $this->context();

        $sections = $this->mySections($sid, $staff);
        $sectionId = $request->integer('section_id') ?: $sections->first()?->id;
        $section = $sections->firstWhere('id', $sectionId);
        $date = $request->date('date')?->format('Y-m-d') ?? now()->format('Y-m-d');

        $roster = collect();
        if ($section) {
            $academics = StudentAcademic::where('school_id', $sid)
                ->where('section_id', $section->id)->where('is_current', true)
                ->whereHas('student', fn ($q) => $q->where('status', 'active')->where('is_trash', false))
                ->with('student:id,name,student_id')->get();

            $marked = StudentAttendance::forSchool($sid)->onDate($date)
                ->whereIn('student_id', $academics->pluck('student_id'))->pluck('status', 'student_id');

            $roster = $academics->filter(fn ($a) => $a->student !== null)
                ->map(fn ($a) => (object) [
                    'student_id' => $a->student_id,
                    'name'       => $a->student->name,
                    'code'       => $a->student->student_id,
                    'status'     => $marked[$a->student_id] ?? 'present',
                ])->sortBy('name')->values();
        }

        return view('staff.attendance', compact('sections', 'section', 'date', 'roster'));
    }

    public function store(Request $request): RedirectResponse
    {
        [$sid, $staff] = $this->context();

        $data = $request->validate([
            'section_id'  => ['required', 'integer'],
            'date'        => ['required', 'date'],
            'statuses'    => ['required', 'array'],
            'statuses.*'  => ['required', 'in:present,absent,late,half_day,leave'],
        ]);

        // The section must be one this teacher leads (service double-checks too).
        $section = Section::where('school_id', $sid)->where('id', $data['section_id'])
            ->where('class_teacher_id', $staff?->id)->firstOrFail();

        $entries = [];
        foreach ($data['statuses'] as $studentId => $status) {
            $entries[] = ['student_id' => (int) $studentId, 'status' => $status];
        }

        $result = $this->attendance->bulkUpsert(
            $sid, $section->class_id, $section->id, $data['date'], $entries, $request->user(),
        );

        return redirect()->route('staff.attendance', ['section_id' => $section->id, 'date' => $data['date']])
            ->with('status', "Attendance saved — {$result['created']} new, {$result['updated']} updated.");
    }

    /** Sections the teacher is the class teacher for. */
    private function mySections(int $sid, ?Staff $staff)
    {
        if (! $staff) {
            return collect();
        }

        return Section::where('school_id', $sid)->where('class_teacher_id', $staff->id)
            ->where('is_trash', false)->with(['schoolClass:id,name', 'shift:id,name'])
            ->orderBy('class_id')->get();
    }

    /** @return array{0:int,1:?Staff} */
    private function context(): array
    {
        $sid = app('current_school_id');

        return [$sid, Staff::where('school_id', $sid)->where('user_id', auth()->id())->first()];
    }
}
