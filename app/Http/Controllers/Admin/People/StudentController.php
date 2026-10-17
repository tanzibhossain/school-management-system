<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Models\AcademicVersion;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentSubject;
use App\Modules\Student\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class StudentController extends Controller
{
    /** Student photos and documents (Student module storage). */
    private const FILE_DISK = 'minio';

    /** Uploaded files are never cached, sniffed or allowed to run script. */
    private const FILE_HEADERS = [
        'Cache-Control' => 'private, no-store',
        'X-Content-Type-Options' => 'nosniff',
        'Content-Security-Policy' => 'sandbox',
    ];

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

        try {
            $this->students->enrolFromAdminForm($schoolId, $validated);
        } catch (HttpExceptionInterface $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.students.index')->with('status', __('Student Enrolled.'));
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

        return back()->with('status', __('Student Updated.'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($id);
        $this->students->deactivate($student);

        return back()->with('status', __('Student Deactivated.'));
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)
            ->with([
                'academics.schoolClass:id,name', 'academics.section:id,name',
                'guardians', 'addresses', 'documents',
            ])->findOrFail($id);

        $years = AcademicYear::where('school_id', $schoolId)->pluck('year', 'id');

        $subjects = StudentSubject::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->with('subjectRelation.subject:id,name')
            ->get();

        $invoices = Invoice::where('school_id', $schoolId)
            ->where('student_id', $student->id)
            ->orderByDesc('id')->limit(200)->get();

        // The online application this student was admitted from, if any —
        // it keeps the details the student record has no place for.
        $application = AdmissionApplication::where('school_id', $schoolId)
            ->where('created_student_id', $student->id)->first(['id', 'reference_number']);

        return view('admin.people.students.show', compact('student', 'years', 'subjects', 'invoices', 'application'));
    }

    public function photo(int $id): StreamedResponse
    {
        $student = Student::where('school_id', app('current_school_id'))->findOrFail($id);
        abort_unless($student->photo && Storage::disk(self::FILE_DISK)->exists($student->photo), 404);

        return Storage::disk(self::FILE_DISK)->response($student->photo, null, self::FILE_HEADERS);
    }

    /** Documents can be any file type, so they always download rather than open in the browser. */
    public function document(int $id, int $documentId): StreamedResponse
    {
        $student = Student::where('school_id', app('current_school_id'))->findOrFail($id);
        $document = $student->documents()->findOrFail($documentId);
        abort_unless(Storage::disk(self::FILE_DISK)->exists($document->file_path), 404);

        return Storage::disk(self::FILE_DISK)->download($document->file_path, $document->original_name, self::FILE_HEADERS);
    }

    public function transfer(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $student = Student::where('school_id', $schoolId)->findOrFail($id);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? 'transfer';

        $this->students->transfer($student, $reason);

        return back()->with('status', __('Student Marked Transferred.'));
    }
}
