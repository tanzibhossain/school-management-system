<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Attendance\Models\AttendanceSetting;
use App\Modules\Attendance\Models\StaffAttendance;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamHall;
use App\Modules\Examination\Models\ExamHallSeat;
use App\Modules\Examination\Models\ExamSeating;
use App\Modules\Examination\Models\ExamSubject;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Leave\Models\LeaveType;
use App\Modules\Leave\Models\StaffLeaveRequest;
use App\Modules\Leave\Models\StudentLeaveRequest;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Operational demo data — demo login accounts; ~a month of student attendance
 * and staff punches (Mon–Fri); mark/exam setup incl. an exam hall with generated
 * seats + seating for the Class 8 exam; leave types + student/staff leave
 * requests in mixed statuses; two months of billing for every student
 * (paid/partial/unpaid — last month's unpaid rows are overdue); and an admission
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

        // Roughly a month of student attendance (school days = Mon–Fri).
        if ($year) {
            $enrolments = StudentAcademic::where('school_id', $sid)
                ->where('academic_year_id', $year->id)->where('is_current', true)->get();

            foreach (range(30, 0) as $back) {
                $date = now()->subDays($back);
                if ($date->isWeekend()) {   // Sat/Sun off — routine week is Mon–Fri
                    continue;
                }
                $d = $date->toDateString();
                foreach ($enrolments as $i => $en) {
                    // Deterministic but varied per student+day.
                    $seed = ($en->student_id + $back) % 20;
                    $status = match (true) {
                        $seed === 0 => 'absent',
                        $seed === 1 => 'late',
                        $seed === 2 => 'half_day',
                        default => 'present',
                    };
                    StudentAttendance::firstOrCreate(
                        ['school_id' => $sid, 'student_id' => $en->student_id, 'date' => $d],
                        [
                            'class_id' => $en->class_id,
                            'section_id' => $en->section_id,
                            'academic_year_id' => $year->id,
                            'status' => $status,
                            'recorded_by' => $adminId,
                        ],
                    );
                }
            }
        }

        // ── Staff attendance for the same period (punch in/out) ─────────────
        $allStaff = Staff::where('school_id', $sid)->where('status', 'active')->get();
        foreach (range(30, 0) as $back) {
            $date = now()->subDays($back);
            if ($date->isWeekend()) {
                continue;
            }
            foreach ($allStaff as $i => $st) {
                $seed = ($st->id + $back) % 15;
                if ($seed === 0) {
                    continue; // absent — no punch row
                }
                $late = $seed === 1;
                $checkIn = $date->copy()->setTime($late ? 9 : 8, $late ? 25 : 50);
                StaffAttendance::firstOrCreate(
                    ['school_id' => $sid, 'staff_id' => $st->id, 'date' => $date->toDateString()],
                    [
                        'check_in' => $checkIn,
                        'check_out' => $date->copy()->setTime(16, 5),
                        'source' => 'manual',
                        'is_auto_closed' => false,
                        'is_incomplete' => false,
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

        // ── A published exam (Class 8) with subjects + one mark division each ──
        // Lets teachers enter marks for their subject; the office calculates results.
        $halfYearly = ExamType::where('school_id', $sid)->where('name', 'Half Yearly')->first();
        $class8 = SchoolClass::where('school_id', $sid)->where('name', 'Class 8')->first();
        if ($year && $halfYearly && $class8) {
            $exam = Exam::firstOrCreate(
                ['school_id' => $sid, 'title' => 'Half Yearly Examination', 'class_id' => $class8->id, 'academic_year_id' => $year->id],
                [
                    'exam_type_id' => $halfYearly->id,
                    'start_date' => now()->addWeek()->toDateString(),
                    'end_date' => now()->addWeeks(2)->toDateString(),
                    'status' => 'published',
                    'seating_strategy' => 'sequential',
                ],
            );

            $relations = SubjectRelation::where('school_id', $sid)->where('class_id', $class8->id)->get();
            foreach ($relations->values() as $i => $rel) {
                $examSubject = ExamSubject::firstOrCreate(
                    ['school_id' => $sid, 'exam_id' => $exam->id, 'subject_relation_id' => $rel->id],
                    [
                        'exam_date' => now()->addWeek()->addDays($i)->toDateString(),
                        'start_time' => '10:00:00',
                        'end_time' => '13:00:00',
                        'full_marks' => 100,
                        'pass_marks' => 33,
                    ],
                );

                MarkDivision::firstOrCreate(
                    ['school_id' => $sid, 'exam_id' => $exam->id, 'exam_subject_id' => $examSubject->id, 'name' => 'Written'],
                    ['max_marks' => 100, 'pass_mark' => 33, 'display_order' => 1],
                );
            }

            // ── Exam hall (8 rows × L3/R3 = 48 seats) + seat the Class 8 batch ──
            $hall = ExamHall::firstOrCreate(
                ['school_id' => $sid, 'name' => 'Exam Hall A'],
                [
                    'description' => 'Main examination hall',
                    'layout_config' => [
                        'rows' => 8,
                        'sides' => [
                            ['label' => 'L', 'seats_per_row' => 3, 'blocked_rows' => []],
                            ['label' => 'R', 'seats_per_row' => 3, 'blocked_rows' => []],
                        ],
                    ],
                ],
            );

            foreach (range(1, 8) as $row) {
                foreach (['L' => 3, 'R' => 3] as $side => $count) {
                    foreach (range(1, $count) as $pos) {
                        ExamHallSeat::firstOrCreate(
                            ['hall_id' => $hall->id, 'row' => $row, 'side' => $side, 'position' => $pos],
                            ['label' => "R{$row}-{$side}{$pos}", 'is_available' => true],
                        );
                    }
                }
            }

            $seats = ExamHallSeat::where('hall_id', $hall->id)->where('is_available', true)
                ->orderBy('row')->orderBy('side')->orderBy('position')->get();
            $class8Enrol = StudentAcademic::where('school_id', $sid)->where('class_id', $class8->id)
                ->where('is_current', true)->orderBy('roll_number')->get();

            foreach ($class8Enrol as $si => $en) {
                if (! isset($seats[$si])) {
                    break;
                }
                ExamSeating::firstOrCreate(
                    ['school_id' => $sid, 'exam_id' => $exam->id, 'student_id' => $en->student_id],
                    [
                        'hall_seat_id' => $seats[$si]->id,
                        'exam_roll' => $en->roll_number,
                        'group_id' => $en->group_id,
                        'section_id' => $en->section_id,
                    ],
                );
            }
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

        // ── Leave requests — students + staff, mixed statuses ───────────────
        $sick = LeaveType::where('school_id', $sid)->where('name', 'Sick Leave')->first();
        $medical = LeaveType::where('school_id', $sid)->where('name', 'Medical Leave')->first();
        $casual = LeaveType::where('school_id', $sid)->where('name', 'Casual Leave')->first();
        $workingDays = fn ($from, $to) => max(1, collect(CarbonPeriod::create($from, $to))->reject(fn ($d) => $d->isWeekend())->count());

        if ($year && ($sick || $medical)) {
            $someEnrol = StudentAcademic::where('school_id', $sid)->where('academic_year_id', $year->id)
                ->where('is_current', true)->take(6)->get();
            $statuses = ['approved', 'pending', 'rejected', 'approved', 'pending', 'approved'];
            foreach ($someEnrol as $i => $en) {
                $from = now()->subDays(10 - $i)->startOfDay();
                $to = $from->copy()->addDays(1 + ($i % 3));
                $status = $statuses[$i % count($statuses)];
                StudentLeaveRequest::firstOrCreate(
                    ['school_id' => $sid, 'student_id' => $en->student_id, 'from_date' => $from->toDateString()],
                    [
                        'class_id' => $en->class_id,
                        'section_id' => $en->section_id,
                        'academic_year_id' => $year->id,
                        'leave_type_id' => ($medical ?? $sick)->id,
                        'to_date' => $to->toDateString(),
                        'working_days' => $workingDays($from, $to),
                        'reason' => 'Family / health reasons',
                        'status' => $status,
                        'requested_by' => $adminId,
                        'approved_by' => $status === 'approved' ? $adminId : null,
                        'approved_at' => $status === 'approved' ? now()->subDays(max(0, 9 - $i)) : null,
                        'rejection_reason' => $status === 'rejected' ? 'Insufficient notice' : null,
                    ],
                );
            }
        }

        if ($sick || $casual) {
            $someStaff = Staff::where('school_id', $sid)->where('status', 'active')->take(4)->get();
            $staffStatuses = ['approved', 'pending', 'approved', 'rejected'];
            foreach ($someStaff as $i => $st) {
                $from = now()->subDays(max(1, 8 - $i * 2))->startOfDay();
                $to = $from->copy()->addDays(($i % 2) + 1);
                $status = $staffStatuses[$i % count($staffStatuses)];
                StaffLeaveRequest::firstOrCreate(
                    ['school_id' => $sid, 'staff_id' => $st->id, 'from_date' => $from->toDateString()],
                    [
                        'leave_type_id' => ($sick ?? $casual)->id,
                        'to_date' => $to->toDateString(),
                        'working_days' => $workingDays($from, $to),
                        'reason' => 'Personal leave',
                        'status' => $status,
                        'requested_by' => $adminId,
                        'approved_by' => $status === 'approved' ? $adminId : null,
                        'approved_at' => $status === 'approved' ? now()->subDays(7) : null,
                        'rejection_reason' => $status === 'rejected' ? 'Coverage unavailable' : null,
                    ],
                );
            }
        }

        // ── Billing: last month + this month tuition invoices ───────────────
        // Statuses mix paid / partial / unpaid; last month's unpaid rows are
        // past due so they surface as "overdue" in reports.
        if ($year) {
            $students = Student::where('school_id', $sid)->orderBy('id')->get();
            $curMonth = (int) date('n');
            $months = [
                ['m' => max(1, $curMonth - 1), 'due' => now()->subMonthNoOverflow()->startOfMonth()->addDays(9), 'past' => true],
                ['m' => $curMonth, 'due' => now()->startOfMonth()->addDays(9), 'past' => false],
            ];

            foreach ($months as $mi => $mo) {
                $mm = str_pad((string) $mo['m'], 2, '0', STR_PAD_LEFT);
                foreach ($students as $i => $st) {
                    $seq = str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
                    $due = 1500;
                    $bucket = ($st->id + $mi) % 4; // 0,3 paid · 1 partial · 2 unpaid
                    $amountPaid = match ($bucket) {
                        0, 3 => $due,
                        1 => 700,
                        default => 0,
                    };
                    $status = $amountPaid >= $due ? 'paid' : ($amountPaid > 0 ? 'partial' : 'unpaid');

                    $invoice = Invoice::firstOrCreate(
                        ['school_id' => $sid, 'invoice_number' => "INV-{$year->year}-{$mm}-{$seq}"],
                        [
                            'student_id' => $st->id,
                            'academic_year_id' => $year->id,
                            'month' => $mo['m'],
                            'amount_due' => $due,
                            'currency' => 'BDT',
                            'amount_paid' => $amountPaid,
                            'credit_applied' => 0,
                            'status' => $status,
                            'due_date' => $mo['due']->toDateString(),
                            'issued_by' => $adminId,
                        ],
                    );

                    if ($amountPaid > 0) {
                        Payment::firstOrCreate(
                            ['school_id' => $sid, 'receipt_number' => "RCV-{$year->year}-{$mm}-{$seq}"],
                            [
                                'invoice_id' => $invoice->id,
                                'student_id' => $st->id,
                                'amount' => $amountPaid,
                                'currency' => 'BDT',
                                'method' => 'cash',
                                'is_reversed' => false,
                                'collected_by' => $adminId,
                                'paid_at' => $mo['due']->copy()->addDay()->toDateString(),
                            ],
                        );
                    }
                }
            }
        }

        // ── Online admission application ────────────────────────────────────
        $desiredClass = SchoolClass::where('school_id', $sid)->where('name', 'Class 3')->first();
        if ($desiredClass && $year) {
            AdmissionApplication::firstOrCreate(
                ['school_id' => $sid, 'reference_number' => 'ADMISSION-'.$year->year.'-0001'],
                [
                    'status' => 'submitted',
                    'applicant_name' => 'Ariful Islam',
                    'gender' => 'male',
                    'dob' => now()->subYears(8)->toDateString(),
                    'desired_class_id' => $desiredClass->id,
                    'desired_academic_year_id' => $year->id,
                    'guardian_name' => 'Shahidul Islam',
                    'guardian_phone' => '01711223344',
                    'guardian_relation' => 'father',
                ],
            );
        }
    }
}
