<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Repositories\AcademicRepository;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(private readonly AcademicRepository $academic) {}

    public function index(int $classId): View
    {
        $schoolId = app('current_school_id');
        $class = SchoolClass::where('school_id', $schoolId)->findOrFail($classId);

        $sections = Section::where('school_id', $schoolId)
            ->where('class_id', $classId)
            ->where('is_trash', false)
            ->with('classTeacher:id,name')
            ->orderBy('name')
            ->get();

        $teachers = Staff::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']);

        return view('admin.setup.sections.index', compact('class', 'sections', 'teachers'));
    }

    public function store(Request $request, int $classId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        SchoolClass::where('school_id', $schoolId)->findOrFail($classId);
        $data = $this->validated($request, $schoolId, $classId, null);

        Section::create($data + ['school_id' => $schoolId, 'class_id' => $classId]);
        $this->academic->flush();

        return back()->with('status', __('Section created.'));
    }

    public function update(Request $request, int $classId, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $section = Section::where('school_id', $schoolId)->where('class_id', $classId)->findOrFail($id);
        $section->update($this->validated($request, $schoolId, $classId, $id));
        $this->academic->flush();

        return back()->with('status', __('Section updated.'));
    }

    public function destroy(int $classId, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $section = Section::where('school_id', $schoolId)->where('class_id', $classId)->findOrFail($id);
        $section->update(['is_trash' => true]);
        $this->academic->flush();

        return back()->with('status', __('Section deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $schoolId, int $classId, ?int $id): array
    {
        $ignore = $id ?? 'NULL';

        return $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:sections,name,{$ignore},id,school_id,{$schoolId},class_id,{$classId},is_trash,0"],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'class_teacher_id' => ['nullable', 'integer', "exists:staff,id,school_id,{$schoolId}"],
        ], [], ['class_teacher_id' => 'class teacher']);
    }
}
