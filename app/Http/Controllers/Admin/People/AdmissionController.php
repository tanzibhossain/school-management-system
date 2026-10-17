<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\OnlineAdmission\Services\AdmissionApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AdmissionController extends Controller
{
    public function __construct(private readonly AdmissionApplicationService $admissions) {}

    public function index(Request $request): View
    {
        $schoolId = app('current_school_id');

        $query = AdmissionApplication::where('school_id', $schoolId);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('admin.people.admissions.index', [
            'applications' => $query->orderByDesc('id')->limit(500)->get(),
            'filters' => $request->only('status'),
        ]);
    }

    public function show(int $id): View
    {
        $schoolId = app('current_school_id');
        $application = AdmissionApplication::where('school_id', $schoolId)->findOrFail($id);

        $sections = $application->desired_class_id
            ? Section::where('school_id', $schoolId)->where('class_id', $application->desired_class_id)->where('is_trash', false)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.people.admissions.show', [
            'application' => $application,
            'sections' => $sections,
            'class' => $application->desired_class_id ? SchoolClass::find($application->desired_class_id) : null,
            'year' => $application->desired_academic_year_id ? AcademicYear::find($application->desired_academic_year_id) : null,
        ]);
    }

    /**
     * Stream an applicant's upload (photo or a custom file field) from private
     * storage. Same role gate as the review page; the path always comes from
     * the application itself, never the request.
     */
    public function file(int $id, string $key): StreamedResponse
    {
        $application = AdmissionApplication::where('school_id', app('current_school_id'))->findOrFail($id);
        $file = $this->admissions->storedFile($application, $key);
        abort_unless($file !== null, 404);

        return Storage::disk($file['disk'])->response($file['path'], null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $application = AdmissionApplication::where('school_id', $schoolId)->findOrFail($id);

        $data = $request->validate([
            'admission_number' => ['required', 'string', 'max:30', "unique:students,admission_number,NULL,id,school_id,{$schoolId}"],
            'section_id' => ['required', 'integer', "exists:sections,id,school_id,{$schoolId}"],
            'roll_number' => ['nullable', 'string', 'max:50'],
        ], [], ['section_id' => 'section', 'admission_number' => 'admission number']);

        try {
            $this->admissions->approve($application, $request->user(), $data);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.admissions.index')->with('status', __('Application Approved — Student Enrolled.'));
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $application = AdmissionApplication::where('school_id', app('current_school_id'))->findOrFail($id);
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        try {
            $this->admissions->reject($application, $request->user(), $reason);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return back()->with('status', __('Application Rejected.'));
    }
}
