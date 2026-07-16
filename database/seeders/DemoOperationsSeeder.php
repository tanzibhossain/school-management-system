<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Attendance\Models\AttendanceSetting;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Operational demo data — demo login accounts, attendance/mark/exam setup,
 * leave types, a slice of billing (invoices + payments), and an admission
 * application. Run after DemoDataSeeder.
 */
class DemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) {
            return;
        }
        $sid = $school->id;
        $admin = User::where('school_id', $sid)->first();
        $adminId = $admin?->id ?? 1;

        $year = AcademicYear::where('school_id', $sid)->where('is_current', true)->first();
        $classes = SchoolClass::where('school_id', $sid)->where('is_trash', false)->get();

        // ── Demo login accounts (teacher / parent / student) ────────────────
        // Link them to existing staff/guardian/student rows so the role-specific
        // areas (and library/messaging) have real people behind them.
        $teacherStaff = Staff::where('school_id', $sid)->whereNotNull('subject_id')->first();
        if ($teacherStaff && ! $teacherStaff->user_id) {
            $tu = User::firstOrCreate(
                ['email' => 'teacher@school.edu.bd'],
                ['school_id' => $sid, 'name' => $teacherStaff->name, 'password' => Hash::make('Teacher@1234'), 'is_active' => true],
            );
            $tu->syncRoles(['teacher']);
            $teacherStaff->update(['user_id' => $tu->id]);
        }

        $student = Student::where('school_id', $sid)->orderBy('id')->first();
        if ($student && ! $student->user_id) {
            $su = User::firstOrCreate(
                ['email' => 'student@school.edu.bd'],
                ['school_id' => $sid, 'name' => $student->name, 'password' => Hash::make('Student@1234'), 'is_active' => true],
            );
            $su->syncRoles(['student']);
            $student->update(['user_id' => $su->id]);

            $guardian = StudentGuardian::where('school_id', $sid)->where('student_id', $student->id)->first();
            if ($guardian && ! $guardian->user_id) {
                $pu = User::firstOrCreate(
                    ['email' => 'parent@school.edu.bd'],
                    ['school_id' => $sid, 'name' => $guardian->name, 'password' => Hash::make('Parent@1234'), 'is_active' => true],
                );
                $pu->syncRoles(['parent']);
                $guardian->update(['user_id' => $pu->id]);
            }
        }

        // ── Attendance settings + one day of attendance ─────────────────────
        AttendanceSetting::firstOrCreate(
            ['school_id' => $sid],
            ['auto_close_policy' => 'closing_time', 'max_shift_hours' => 12, 'edit_window_days' => 7, 'late_threshold_minutes' => 15, 'leave_counts_in_denominator' => true],
        );

        if ($year) {
            $today = now()->toDateString();
            $enrolments = StudentAcademic::where('school_id', $sid)
                ->where('academic_year_id', $year->id)->where('is_current', true)->get();
            foreach ($enrolments as $i => $en) {
                StudentAttendance::firstOrCreate(
                    ['school_id' => $sid, 'student_id' => $en->student_id, 'date' => $today],
                    [
                        'class_id'         => $en->class_id,
                        'section_id'       => $en->section_id,
                        'academic_year_id' => $year->id,
                        'status'           => $i % 7 === 0 ? 'absent' : ($i % 5 === 0 ? 'late' : 'present'),
                        'recorded_by'      => $adminId,
                    ],
                );
            }
        }

        // ── Mark settings + grade boundaries (bd_national) per class ─────────
        $grades = [
            ['A+', 80, 100, 5.00], ['A', 70, 79, 4.00], ['A-', 60, 69, 3.50],
            ['B', 50, 59, 3.00], ['C', 40, 49, 2.00], ['D', 33, 39, 1.00], ['F', 0, 32, 0.00],
        ];
        foreach ($classes as $class) {
            MarkSetting::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id],
                ['mode' => 'mark', 'result_strategy' => 'bd_national', 'show_merit_position' => true, 'grace_marks_cap' => 3],
            );
            foreach ($grades as $g) {
                GradeBoundary::firstOrCreate(
                    ['school_id' => $sid, 'class_id' => $class->id, 'grade_label' => $g[0]],
                    ['min_percent' => $g[1], 'max_percent' => $g[2], 'gpa_point' => $g[3]],
                );
            }
        }

        // ── Exam types ──────────────────────────────────────────────────────
        foreach (['First Term', 'Half Yearly', 'Annual Exam'] as $et) {
            ExamType::firstOrCreate(['school_id' => $sid, 'name' => $et], ['is_active' => true]);
        }

        // ── Leave types ─────────────────────────────────────────────────────
        foreach ([
            ['name' => 'Sick Leave',    'applies_to' => 'both',    'max' => 14, 'paid' => true],
            ['name' => 'Casual Leave',  'applies_to' => 'staff',   'max' => 10, 'paid' => true],
            ['name' => 'Medical Leave', 'applies_to' => 'student', 'max' => 20, 'paid' => false],
        ] as $lt) {
            LeaveType::firstOrCreate(
                ['school_id' => $sid, 'name' => $lt['name']],
                ['applies_to' => $lt['applies_to'], 'max_days_per_year' => $lt['max'], 'requires_attachment' => false, 'is_paid' => $lt['paid'], 'is_active' => true],
            );
        }

        // ── Billing: monthly tuition invoices + some payments ───────────────
        if ($year) {
            $students = Student::where('school_id', $sid)->orderBy('id')->take(8)->get();
            $month = (int) date('n');
            foreach ($students as $i => $st) {
                $seq = str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
                $paid = $i % 2 === 0;
                $invoice = Invoice::firstOrCreate(
                    ['school_id' => $sid, 'invoice_number' => "INV-{$year->year}-{$seq}"],
                    [
                        'student_id'       => $st->id,
                        'academic_year_id' => $year->id,
                        'month'            => $month,
                        'amount_due'       => 1500,
                        'currency'         => 'BDT',
                        'amount_paid'      => $paid ? 1500 : 0,
                        'credit_applied'   => 0,
                        'status'           => $paid ? 'paid' : 'unpaid',
                        'due_date'         => now()->startOfMonth()->addDays(9)->toDateString(),
                        'issued_by'        => $adminId,
                    ],
                );

                if ($paid) {
                    Payment::firstOrCreate(
                        ['school_id' => $sid, 'receipt_number' => "RCV-{$year->year}-{$seq}"],
                        [
                            'invoice_id'   => $invoice->id,
                            'student_id'   => $st->id,
                            'amount'       => 1500,
                            'currency'     => 'BDT',
                            'method'       => 'cash',
                            'is_reversed'  => false,
                            'collected_by' => $adminId,
                            'paid_at'      => now()->subDays($i),
                        ],
                    );
                }
            }
        }

        // ── Online admission application ────────────────────────────────────
        $desiredClass = SchoolClass::where('school_id', $sid)->where('name', 'Class 3')->first();
        if ($desiredClass && $year) {
            AdmissionApplication::firstOrCreate(
                ['school_id' => $sid, 'reference_number' => 'ADMISSION-' . $year->year . '-0001'],
                [
                    'status'                   => 'submitted',
                    'applicant_name'           => 'Ariful Islam',
                    'gender'                   => 'male',
                    'dob'                      => now()->subYears(8)->toDateString(),
                    'desired_class_id'         => $desiredClass->id,
                    'desired_academic_year_id' => $year->id,
                    'guardian_name'            => 'Shahidul Islam',
                    'guardian_phone'           => '01711223344',
                    'guardian_relation'        => 'father',
                ],
            );
        }
    }
}
