<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Attendance\Services\AttendanceService;
use App\Modules\Student\Models\StudentAcademic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Laravel\Sanctum\TransientToken;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendance) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');

        $classId = $request->integer('class_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;
        $date = $request->date('date')?->format('Y-m-d') ?? now()->format('Y-m-d');

        $roster = collect();
        if ($classId) {
            $academics = StudentAcademic::where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
                ->where('is_current', true)
                ->whereHas('student', fn ($q) => $q->where('status', 'active')->where('is_trash', false))
                ->with('student:id,name,student_id')
                ->get();

            $marked = StudentAttendance::forSchool($schoolId)->onDate($date)
                ->whereIn('student_id', $academics->pluck('student_id'))
                ->pluck('status', 'student_id');

            $roster = $academics
                ->filter(fn ($a) => $a->student !== null)
                ->map(fn ($a) => (object) [
                    'student_id' => $a->student_id,
                    'name' => $a->student->name,
                    'code' => $a->student->student_id,
                    'status' => $marked[$a->student_id] ?? 'present',
                ])->sortBy('name')->values();
        }

        return view('admin.academics.attendance.index', [
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'sections' => Section::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'class_id']),
            'roster' => $roster,
            'classId' => $classId,
            'sectionId' => $sectionId,
            'date' => $date,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'section_id' => ['nullable', 'integer', "exists:sections,id,school_id,{$schoolId}"],
            'date' => ['required', 'date'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', 'in:present,absent,late,half_day,leave'],
        ]);

        $entries = [];
        foreach ($data['statuses'] as $studentId => $status) {
            $entries[] = ['student_id' => (int) $studentId, 'status' => $status];
        }

        // Session (web) admin has no Sanctum token; the service gates corrections
        // and section-ownership on tokenCan('admin:*'). Assign a TransientToken
        // (its can() returns true) — the same primitive Sanctum uses for SPA
        // session auth — so a role-gated admin can record for any section.
        $recorder = $request->user();
        $recorder->withAccessToken(new TransientToken);

        $result = $this->attendance->bulkUpsert(
            $schoolId, $data['class_id'], $data['section_id'] ?? null, $data['date'], $entries, $recorder,
        );

        return redirect()->route('admin.attendance.index', array_filter([
            'class_id' => $data['class_id'],
            'section_id' => $data['section_id'] ?? null,
            'date' => $data['date'],
        ]))->with('status', "Attendance saved — {$result['created']} new, {$result['updated']} updated.");
    }
}
