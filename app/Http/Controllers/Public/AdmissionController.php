<?php

namespace App\Http\Controllers\Public;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\OnlineAdmission\Services\AdmissionApplicationService;
use App\Modules\School\Models\School;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $sid = $school->id;

        $data = $request->validate([
            // Student
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['nullable', 'string', 'max:100'],
            // Age gate is configured per class (classes.min_age / max_age); null = no limit.
            'dob'          => ['required', 'date', function ($attr, $value, $fail) use ($request, $sid) {
                if (! strtotime((string) $value)) {
                    return;
                }
                $class = \App\Modules\Academic\Models\SchoolClass::where('school_id', $sid)->find($request->input('desired_class_id'));
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
            'gender'       => ['required', 'in:male,female,other'],
            'religion'     => ['required', 'string', 'max:50'],
            'blood_group'  => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
            'desired_class_id'         => ['required', 'integer', "exists:classes,id,school_id,{$sid}"],
            'desired_academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$sid}"],
            'previous_school' => ['required', 'string', 'max:200'],
            'gpa'          => ['required', 'string', 'max:20'],
            'student_phone' => ['nullable', 'string', 'max:30'],
            'photo'        => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            // Parents
            'father_name'  => ['required', 'string', 'max:150'],
            'father_phone' => ['required', 'string', 'max:30'],
            'father_nid'   => ['required', 'string', 'max:50'],
            'mother_name'  => ['required', 'string', 'max:150'],
            'mother_nid'   => ['required', 'string', 'max:50'],
            // Guardian
            'guardian_type' => ['required', 'in:father,mother,other'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],
            // Address
            'present_address'   => ['required', 'string', 'max:500'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'is_permanent_same' => ['nullable', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        $guardianType  = $data['guardian_type'];
        $guardianName  = $data['guardian_name'] ?? ($guardianType === 'mother' ? $data['mother_name'] : $data['father_name']);
        $guardianPhone = $data['guardian_phone'] ?? $data['father_phone'];
        $guardianRelation = in_array($guardianType, ['father', 'mother'], true) ? $guardianType : 'other';

        // ── Duplicate protection ────────────────────────────────────────────
        $duplicate = AdmissionApplication::where('school_id', $sid)
            ->whereIn('status', ['submitted', 'approved'])
            ->where(function ($q) use ($data, $guardianPhone) {
                $q->where('birth_certificate_no', $data['birth_certificate_no'])
                    ->orWhere('father_nid', $data['father_nid'])
                    ->orWhere('guardian_phone', $guardianPhone);
                if (! empty($data['student_phone'])) {
                    $q->orWhere('student_phone', $data['student_phone']);
                }
            })
            ->exists();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'duplicate' => 'An application already exists for this student or guardian (matching birth certificate, NID, or phone number).',
            ]);
        }

        $photoPath = $request->hasFile('photo') ? $request->file('photo')->store('admissions', 'public') : null;

        $application = $this->applications->submit($sid, [
            'applicant_name'       => trim($data['first_name'] . ' ' . ($data['last_name'] ?? '')),
            'gender'               => $data['gender'],
            'dob'                  => $data['dob'],
            'blood_group'          => $data['blood_group'] ?? null,
            'birth_certificate_no' => $data['birth_certificate_no'],
            'student_phone'        => $data['student_phone'] ?? null,
            'father_nid'           => $data['father_nid'],
            'guardian_nid'         => $guardianType === 'mother' ? $data['mother_nid'] : $data['father_nid'],
            'desired_class_id'     => $data['desired_class_id'],
            'desired_academic_year_id' => $data['desired_academic_year_id'],
            'guardian_name'        => $guardianName,
            'guardian_phone'       => $guardianPhone,
            'guardian_relation'    => $guardianRelation,
            'notes'                => $data['notes'] ?? null,
            'form_data'            => [
                'first_name'       => $data['first_name'],
                'last_name'        => $data['last_name'] ?? null,
                'religion'         => $data['religion'],
                'previous_school'  => $data['previous_school'],
                'gpa'              => $data['gpa'],
                'father_name'      => $data['father_name'],
                'father_phone'     => $data['father_phone'],
                'mother_name'      => $data['mother_name'],
                'mother_nid'       => $data['mother_nid'],
                'guardian_type'    => $guardianType,
                'guardian_relationship' => $data['guardian_relationship'] ?? null,
                'present_address'  => $data['present_address'],
                'permanent_address' => $data['permanent_address'] ?? null,
                'is_permanent_same' => (bool) ($data['is_permanent_same'] ?? false),
                'photo'            => $photoPath,
            ],
        ]);

        return back()->with('admission_reference', $application->reference_number);
    }
}
