<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Database\Seeder;

/**
 * Foundation demo data: academic structure (classes 3–8, sections A/B with
 * Morning/Day shifts, subjects), staff (departments, designations, teachers with
 * subjects), students + guardians + enrolments, fee structure, and notices.
 * Run before DemoOperationsSeeder / DemoOptionalSeeder.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) {
            return;
        }
        $sid = $school->id;
        $admin = User::where('school_id', $sid)->first();

        // ── Academic year ───────────────────────────────────────────────────
        $year = AcademicYear::firstOrCreate(
            ['school_id' => $sid, 'year' => (int) date('Y')],
            ['is_current' => true],
        );

        // ── Shifts ──────────────────────────────────────────────────────────
        $morning = AcademicShift::firstOrCreate(['school_id' => $sid, 'name' => 'Morning Shift'], ['is_trash' => false]);
        $day     = AcademicShift::firstOrCreate(['school_id' => $sid, 'name' => 'Day Shift'], ['is_trash' => false]);

        // ── Subjects ────────────────────────────────────────────────────────
        $subjectNames = ['Bangla', 'English', 'Mathematics', 'Science', 'Social Science', 'Religion', 'ICT'];
        $subjects = [];
        foreach ($subjectNames as $i => $name) {
            $subjects[$name] = Subject::firstOrCreate(
                ['school_id' => $sid, 'name' => $name],
                ['sub_code' => 'SUB' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT), 'is_trash' => false],
            );
        }

        // ── Classes 3–8, each with sections A (Morning) & B (Day) ───────────
        $sectionMap = [];
        $classMap = [];
        foreach (range(3, 8) as $n) {
            $class = SchoolClass::firstOrCreate(
                ['school_id' => $sid, 'name' => "Class {$n}"],
                ['min_age' => $n + 5, 'max_age' => $n + 8, 'is_trash' => false],
            );
            $classMap[$n] = $class;

            $sectionMap[$n]['A'] = Section::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id, 'name' => 'A'],
                ['capacity' => 40, 'shift_id' => $morning->id, 'is_trash' => false],
            );
            $sectionMap[$n]['B'] = Section::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id, 'name' => 'B'],
                ['capacity' => 40, 'shift_id' => $day->id, 'is_trash' => false],
            );

            // Ensure shift is set even if the section pre-existed without one.
            $sectionMap[$n]['A']->update(['shift_id' => $morning->id]);
            $sectionMap[$n]['B']->update(['shift_id' => $day->id]);

            // Map every subject to this class.
            foreach ($subjects as $subject) {
                SubjectRelation::firstOrCreate([
                    'school_id' => $sid, 'subject_id' => $subject->id, 'class_id' => $class->id,
                ]);
            }
        }

        // ── Departments + designations ──────────────────────────────────────
        $admDept = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Administrative']);
        $acaDept = Department::firstOrCreate(['school_id' => $sid, 'name' => 'Academic']);

        $desig = [];
        foreach ([
            'Head Teacher', 'Assistant Head Teacher', 'Librarian', 'Admission Officer',
            'Accounts Officer', 'Teacher', 'Assistant Teacher',
        ] as $name) {
            $desig[$name] = Designation::firstOrCreate(['school_id' => $sid, 'name' => $name]);
        }

        // ── Staff ───────────────────────────────────────────────────────────
        $adminStaff = [
            ['name' => 'Abdul Karim',   'gender' => 'male',   'desig' => 'Head Teacher'],
            ['name' => 'Ayesha Rahman', 'gender' => 'female', 'desig' => 'Assistant Head Teacher'],
            ['name' => 'Nurul Islam',   'gender' => 'male',   'desig' => 'Librarian'],
            ['name' => 'Shirin Akter',  'gender' => 'female', 'desig' => 'Admission Officer'],
            ['name' => 'Jahangir Alam', 'gender' => 'male',   'desig' => 'Accounts Officer'],
        ];
        $teachingStaff = [
            ['name' => 'Mohammad Hasan', 'gender' => 'male',   'desig' => 'Teacher',           'subject' => 'Mathematics'],
            ['name' => 'Rehana Begum',   'gender' => 'female', 'desig' => 'Teacher',           'subject' => 'English'],
            ['name' => 'Kamrul Islam',   'gender' => 'male',   'desig' => 'Teacher',           'subject' => 'Bangla'],
            ['name' => 'Sabina Yasmin',  'gender' => 'female', 'desig' => 'Teacher',           'subject' => 'Science'],
            ['name' => 'Tariq Aziz',     'gender' => 'male',   'desig' => 'Teacher',           'subject' => 'Social Science'],
            ['name' => 'Farhana Haque',  'gender' => 'female', 'desig' => 'Assistant Teacher', 'subject' => 'ICT'],
            ['name' => 'Mizanur Rahman', 'gender' => 'male',   'desig' => 'Assistant Teacher', 'subject' => 'Religion'],
        ];

        $staffByName = [];
        $seq = 1;
        foreach ($adminStaff as $row) {
            $staffByName[$row['name']] = Staff::firstOrCreate(
                ['school_id' => $sid, 'employee_id' => 'EMP-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT)],
                [
                    'name'            => $row['name'],
                    'gender'          => $row['gender'],
                    'designation_id'  => $desig[$row['desig']]->id,
                    'department_id'   => $admDept->id,
                    'employment_type' => 'permanent',
                    'basic_salary'    => 35000,
                    'status'          => 'active',
                    'joining_date'    => now()->subYears(5)->toDateString(),
                ],
            );
            $seq++;
        }
        foreach ($teachingStaff as $row) {
            $staffByName[$row['name']] = Staff::firstOrCreate(
                ['school_id' => $sid, 'employee_id' => 'EMP-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT)],
                [
                    'name'            => $row['name'],
                    'gender'          => $row['gender'],
                    'designation_id'  => $desig[$row['desig']]->id,
                    'department_id'   => $acaDept->id,
                    'subject_id'      => $subjects[$row['subject']]->id,
                    'employment_type' => 'permanent',
                    'basic_salary'    => 28000,
                    'status'          => 'active',
                    'joining_date'    => now()->subYears(3)->toDateString(),
                ],
            );
            $seq++;
        }

        // Assign class teachers to sections (round-robin over teaching staff).
        $teacherIds = collect($teachingStaff)->map(fn ($r) => $staffByName[$r['name']]->id)->values();
        $ti = 0;
        foreach ($sectionMap as $sections) {
            foreach ($sections as $section) {
                $section->update(['class_teacher_id' => $teacherIds[$ti % $teacherIds->count()]]);
                $ti++;
            }
        }

        // ── Students + guardians + enrolments ───────────────────────────────
        $firstNames = ['Rahim', 'Fatima', 'Sadia', 'Tanvir', 'Nusrat', 'Imran', 'Rakib', 'Sumaiya',
            'Arif', 'Mitu', 'Jubayer', 'Rima', 'Shakil', 'Tania', 'Nadia', 'Sohel',
            'Habib', 'Rupa', 'Sajib', 'Mou', 'Rasel', 'Lima', 'Fahim', 'Popy'];
        $lastNames = ['Uddin', 'Akter', 'Islam', 'Ahmed', 'Jahan', 'Hossain', 'Khan', 'Chowdhury'];
        $guardianNames = ['Karim Uddin', 'Jamal Uddin', 'Nurul Islam', 'Bashir Ahmed', 'Kamal Hossain',
            'Selim Hossain', 'Aminul Haque', 'Rafiqul Islam'];

        $studentSeq = 1;
        foreach (range(3, 8) as $n) {
            foreach (['A', 'B'] as $sec) {
                $section = $sectionMap[$n][$sec];
                $shiftId = $sec === 'A' ? $morning->id : $day->id;

                for ($k = 0; $k < 2; $k++) {
                    $idx = $studentSeq - 1;
                    $name = $firstNames[$idx % count($firstNames)] . ' ' . $lastNames[$idx % count($lastNames)];
                    $gender = $idx % 2 === 0 ? 'male' : 'female';
                    $code = str_pad((string) $studentSeq, 3, '0', STR_PAD_LEFT);

                    $student = Student::firstOrCreate(
                        ['school_id' => $sid, 'admission_number' => "ADM-{$year->year}-{$code}"],
                        [
                            'student_id' => "STD-{$year->year}-{$code}",
                            'name'       => $name,
                            'gender'     => $gender,
                            'status'     => 'active',
                            'dob'        => now()->subYears($n + 5)->toDateString(),
                        ],
                    );

                    StudentAcademic::firstOrCreate(
                        ['school_id' => $sid, 'student_id' => $student->id, 'academic_year_id' => $year->id],
                        [
                            'class_id'    => $section->class_id,
                            'section_id'  => $section->id,
                            'shift_id'    => $shiftId,
                            'roll_number' => (string) ($k + 1),
                            'is_current'  => true,
                        ],
                    );

                    StudentGuardian::firstOrCreate(
                        ['school_id' => $sid, 'student_id' => $student->id, 'relation' => 'father'],
                        [
                            'name'       => $guardianNames[$idx % count($guardianNames)],
                            'phone'      => '017' . str_pad((string) (10000000 + $studentSeq), 8, '0', STR_PAD_LEFT),
                            'is_primary' => true,
                        ],
                    );

                    $studentSeq++;
                }
            }
        }

        // ── Fee structure ───────────────────────────────────────────────────
        $monthlyCat = FeeCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Monthly Recurring Fees'], ['is_active' => true]);
        $yearlyCat  = FeeCategory::firstOrCreate(['school_id' => $sid, 'name' => 'Yearly Recurring Fees'], ['is_active' => true]);

        $feeItems = [
            ['cat' => $monthlyCat, 'name' => 'Tuition Fee',   'amount' => 800,  'frequency' => 'monthly',  'due_day' => 10,   'mandatory' => true],
            ['cat' => $monthlyCat, 'name' => 'Transport Fee', 'amount' => 500,  'frequency' => 'monthly',  'due_day' => 10,   'mandatory' => false],
            ['cat' => $monthlyCat, 'name' => 'Lab Fee',       'amount' => 200,  'frequency' => 'monthly',  'due_day' => 10,   'mandatory' => false],
            ['cat' => $yearlyCat,  'name' => 'Admission Fee', 'amount' => 2000, 'frequency' => 'one_time', 'due_day' => null, 'mandatory' => true],
            ['cat' => $yearlyCat,  'name' => 'Exam Fee',      'amount' => 500,  'frequency' => 'yearly',   'due_day' => null, 'mandatory' => true],
        ];
        foreach ($feeItems as $fi) {
            FeeItem::firstOrCreate(
                ['school_id' => $sid, 'name' => $fi['name'], 'academic_year_id' => $year->id],
                [
                    'category_id'  => $fi['cat']->id,
                    'amount'       => $fi['amount'],
                    'frequency'    => $fi['frequency'],
                    'due_day'      => $fi['due_day'],
                    'is_mandatory' => $fi['mandatory'],
                    'is_active'    => true,
                ],
            );
        }

        // ── Notices ─────────────────────────────────────────────────────────
        if ($admin) {
            foreach ([
                ['title' => 'Admission open for the new academic year', 'body' => 'Applications for classes 3 to 8 are now open. Apply online or visit the school office.', 'is_pinned' => true],
                ['title' => 'Annual sports day on Friday', 'body' => 'The annual sports day will be held this Friday on the school ground. All students must attend.', 'is_pinned' => false],
                ['title' => 'Half-yearly examination schedule published', 'body' => 'The half-yearly examination routine has been published. Collect it from your class teacher.', 'is_pinned' => false],
            ] as $nn) {
                Announcement::firstOrCreate(
                    ['school_id' => $sid, 'title' => $nn['title']],
                    [
                        'created_by' => $admin->id,
                        'body'       => $nn['body'],
                        'audience'   => 'all',
                        'is_pinned'  => $nn['is_pinned'],
                        'publish_at' => now()->subDays(2),
                    ],
                );
            }
        }
    }
}
