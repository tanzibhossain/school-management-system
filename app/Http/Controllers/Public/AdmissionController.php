<?php

namespace App\Http\Controllers\Public;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\OnlineAdmission\Services\AdmissionApplicationService;
use App\Modules\School\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

/**
 * Public online-admission submission. The form is rendered by the Website
 * `admission_form` block. Core fields map to columns the approval→enrolment flow
 * needs; the rest are stored in form_data (JSON) so schools can add/hide fields
 * and the form stays global-friendly. Duplicate applications are rejected.
 */
class AdmissionController extends Controller
{
    public function __construct(private readonly AdmissionApplicationService $applications) {}

    public function submit(Request $request): RedirectResponse
    {
        $school = School::current();
        abort_unless($school, 404);

        $data = $request->validate($this->rules($request, $school->id));
        $photo = $request->file('photo');

        // Throws a 'duplicate' ValidationException -> back with input + error.
        $application = $this->applications->submitPublicForm(
            $school->id,
            $data,
            $photo instanceof UploadedFile ? $photo : null,
        );

        return back()->with('admission_reference', $application->reference_number);
    }

    /** @return array<string, mixed> */
    private function rules(Request $request, int $sid): array
    {
        return [
            // Student
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            // Age gate is configured per class (classes.min_age / max_age); null = no limit.
            'dob' => ['required', 'date', function ($attr, $value, $fail) use ($request, $sid) {
                if (! strtotime((string) $value)) {
                    return;
                }
                $class = SchoolClass::where('school_id', $sid)->find($request->integer('desired_class_id'));
                if (! $class) {
                    return;
                }
                $age = Carbon::parse($value)->age;
                if ($class->min_age !== null && $age < $class->min_age) {
                    $fail("The applicant must be at least {$class->min_age} years old for this class.");
                }
                if ($class->max_age !== null && $age > $class->max_age) {
                    $fail("The applicant must be no older than {$class->max_age} years for this class.");
                }
            }],
            'birth_certificate_no' => ['required', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female,other'],
            'religion' => ['required', 'string', 'max:50'],
            'blood_group' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'desired_class_id' => ['required', 'integer', "exists:classes,id,school_id,{$sid}"],
            'desired_academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$sid}"],
            'previous_school' => ['required', 'string', 'max:200'],
            'gpa' => ['required', 'string', 'max:20'],
            'student_phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            // Parents
            'father_name' => ['required', 'string', 'max:150'],
            'father_phone' => ['required', 'string', 'max:30'],
            'father_nid' => ['required', 'string', 'max:50'],
            'mother_name' => ['required', 'string', 'max:150'],
            'mother_nid' => ['required', 'string', 'max:50'],
            // Guardian
            'guardian_type' => ['required', 'in:father,mother,other'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],
            // Address
            'present_address' => ['required', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'is_permanent_same' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
