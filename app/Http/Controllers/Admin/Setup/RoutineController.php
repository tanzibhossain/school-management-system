<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Models\RoutineRoom;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Academic\Services\RoutineSchedulingService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class RoutineController extends Controller
{
    public const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    public function __construct(private readonly RoutineSchedulingService $scheduling) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');
        $classId = $request->integer('class_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;

        $cells = collect();
        if ($classId && $sectionId) {
            $cells = ClassRoutine::where('school_id', $schoolId)
                ->where('class_id', $classId)->where('section_id', $sectionId)
                ->with(['subject:id,name', 'teacher:id,name', 'room:id,name'])
                ->get()
                ->keyBy(fn ($c) => $c->period_id.':'.$c->day_of_week);
        }

        return view('admin.setup.routine.index', [
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'sections' => Section::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'class_id']),
            'periods' => RoutinePeriod::where('school_id', $schoolId)->where('is_trash', false)->orderBy('start_time')->get(),
            'rooms' => RoutineRoom::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'subjects' => $classId ? SubjectRelation::where('school_id', $schoolId)->where('class_id', $classId)->with('subject:id,name')->get() : collect(),
            'teachers' => Staff::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'cells' => $cells,
            'classId' => $classId,
            'sectionId' => $sectionId,
            'days' => self::DAYS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'section_id' => ['required', 'integer', "exists:sections,id,school_id,{$schoolId}"],
            'subject_id' => ['required', 'integer', "exists:subjects,id,school_id,{$schoolId}"],
            'teacher_id' => ['nullable', 'integer', "exists:staff,id,school_id,{$schoolId}"],
            'room_id' => ['required', 'integer', "exists:routine_rooms,id,school_id,{$schoolId}"],
            'period_id' => ['required', 'integer', "exists:routine_periods,id,school_id,{$schoolId}"],
            'day_of_week' => ['required', 'in:'.implode(',', self::DAYS)],
        ], [], ['subject_id' => 'subject', 'teacher_id' => 'teacher', 'room_id' => 'room', 'period_id' => 'period']);

        if ($this->scheduling->hasConflict($schoolId, $data['room_id'], $data['section_id'], $data['period_id'], $data['day_of_week'])) {
            return back()->with('error', __('That Room Or Section Is Already Booked For This Period And Day.'));
        }

        ClassRoutine::create($data + ['school_id' => $schoolId]);

        return redirect()->route('admin.routine.index', ['class_id' => $data['class_id'], 'section_id' => $data['section_id']])
            ->with('status', __('Class Added To Routine.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $cell = ClassRoutine::where('school_id', app('current_school_id'))->findOrFail($id);
        $classId = $cell->class_id;
        $sectionId = $cell->section_id;
        $cell->delete();

        return redirect()->route('admin.routine.index', ['class_id' => $classId, 'section_id' => $sectionId])
            ->with('status', __('Routine Entry Removed.'));
    }
}
