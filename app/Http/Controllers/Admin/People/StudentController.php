<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Models\AcademicVersion;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentSubject;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class StudentController extends Controller
{
    public function __construct(private readonly StudentService $students) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');

        $query = Student::where('school_id', $schoolId)
            ->where('is_trash', false)
            ->with(['currentAcademic.schoolClass:id,name', 'currentAcademic.section:id,name', 'primaryGuardian']);

        if ($request->filled('class_id')) {
            $query->whereHas('currentAcademic', fn ($q) => $q->where('class_id', $request->integer('class_id')));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.people.students.index', [
            'students' => $query->orderBy('name')->get(),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['class_id', 'status']),
        ]);
    }

    public function create(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.people.students.create', [
            'years' => AcademicYear::where('school_id', $schoolId)->where('is_trash', false)->orderByDesc('is_current')->orderByDesc('year')->get(),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'sections' => Section::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'class_id']),
            'versions' => AcademicVersion::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'groups' => AcademicGroup::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            'shifts' => AcademicShift::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'religion' => ['nullable', 'string', 'max:50'],
            'admission_number' => ['required', 'string', 'max:30', "unique:students,admission_number,NULL,id,school_id,{$schoolId}"],
            'academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$schoolId}"],
            'class_id' => ['required', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'section_id' => ['required', 'integer', "exists:sections,id,school_id,{$schoolId}"],
            'version_id' => ['nullable', 'integer', "exists:versions,id,school_id,{$schoolId}"],
            'group_id' => ['nullable', 'integer', "exists:groups,id,school_id,{$schoolId}"],
            'shift_id' => ['nullable', 'integer', "exists:shifts,id,school_id,{$schoolId}"],
            'roll_number' => ['nullable', 'string', 'max:50'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relation' => ['nullable', 'in:father,mother,local_guardian,other'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email'],
        ]);

        $studentData = array_filter([
            'name' => $validated['name'],
            'gender' => $validated['gender'] ?? null,
            'dob' => $validated['dob'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'religion' => $validated['religion'] ?? null,
            'admission_number' => $validated['admission_number'] ?? null,
        ], fn ($v) => $v !== null);

        $academicData = array_filter([
            'academic_year_id' => $validated['academic_year_id'],
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'],
            'version_id' => $validated['version_id'] ?? null,
            'group_id' => $validated['group_id'] ?? null,
            'shift_id' => $validated['shift_id'] ?? null,
            'roll_number' => $validated['roll_number'] ?? null,
        ], fn ($v) => $v !== null);

        $guardians = [];
        if (! empty($validated['guardian_name'])) {
            $guardians[] = array_filter([
                'name' => $validated['guardian_name'],
                'relation' => $validated['guardian_relation'] ?? 'other',
                'phone' => $validated['guardian_phone'] ?? null,
                'email' => $validated['guardian_email'] ?? null,
                'is_primary' => true,
            ], fn ($v) => $v !== null);
        }

        try {
            $this->students->enrol($schoolId, $studentData, $academicData, $guardians);
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.students.index')->with('status', 'Student enrolled.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'religion' => ['nullable', 'string', 'max:50'],
        ]);

        $student->update($data); // StudentObserver flushes the student cache on saved

        return back()->with('status', 'Student updated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($id);
        $this->students->deactivate($student);

        return back()->with('status', 'Student deactivated.');
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)
            ->with([
                'academics.schoolClass:id,name', 'academics.section:id,name',
                'guardians',
            ])->findOrFail($id);

        $years = AcademicYear::where('school_id', $schoolId)->pluck('year', 'id');

        $subjects = StudentSubject::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with('subjectRelation.subject:id,name')
            ->get();

        $invoices = Invoice::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderByDesc('id')->limit(200)->get();

        return view('admin.people.students.show', compact('student', 'years', 'subjects', 'invoices'));
    }

    public function transfer(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($id);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? 'transfer';

        $this->students->transfer($student, $reason);

        return back()->with('status', 'Student marked transferred.');
    }
}
