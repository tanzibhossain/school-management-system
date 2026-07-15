<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Models\SubjectRelation;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\School\Models\School;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Student\Models\StudentGuardian;
use Illuminate\Database\Seeder;

/**
 * Populates the demo school with a realistic slice of data — academic structure,
 * subjects, staff, students + guardians, and notices — so the whole system can be
 * explored end-to-end (public site, admin, academics) right after install.
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

        // ── Classes + one section each ──────────────────────────────────────
        $sections = [];
        foreach (['Six', 'Seven', 'Eight'] as $className) {
            $class = SchoolClass::firstOrCreate(['school_id' => $sid, 'name' => $className]);
            $sections[$className] = Section::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class->id, 'name' => 'A'],
                ['capacity' => 40],
            );

            // ── Subjects mapped to each class ───────────────────────────────
            foreach (['Bangla', 'English', 'Mathematics', 'Science'] as $subjectName) {
                $subject = Subject::firstOrCreate(['school_id' => $sid, 'name' => $subjectName]);
                SubjectRelation::firstOrCreate([
                    'school_id' => $sid, 'subject_id' => $subject->id, 'class_id' => $class->id,
                ]);
            }
        }

        // ── Staff (teachers) ────────────────────────────────────────────────
        $headTeacher = Designation::firstOrCreate(['school_id' => $sid, 'name' => 'Head Teacher']);
        $asstTeacher = Designation::firstOrCreate(['school_id' => $sid, 'name' => 'Assistant Teacher']);

        $staffRows = [
            ['name' => 'Abdul Karim',   'gender' => 'male',   'designation_id' => $headTeacher->id],
            ['name' => 'Ayesha Rahman', 'gender' => 'female', 'designation_id' => $asstTeacher->id],
            ['name' => 'Mohammad Hasan', 'gender' => 'male',  'designation_id' => $asstTeacher->id],
        ];
        foreach ($staffRows as $i => $row) {
            Staff::firstOrCreate(
                ['school_id' => $sid, 'employee_id' => 'EMP-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                $row + ['status' => 'active', 'joining_date' => now()->subYears(3)->toDateString()],
            );
        }

        // ── Students + guardians ────────────────────────────────────────────
        $studentRows = [
            ['name' => 'Rahim Uddin',    'gender' => 'male',   'class' => 'Six',   'guardian' => 'Karim Uddin'],
            ['name' => 'Fatima Akter',   'gender' => 'female', 'class' => 'Six',   'guardian' => 'Jamal Uddin'],
            ['name' => 'Sadia Islam',    'gender' => 'female', 'class' => 'Seven', 'guardian' => 'Nurul Islam'],
            ['name' => 'Tanvir Ahmed',   'gender' => 'male',   'class' => 'Seven', 'guardian' => 'Bashir Ahmed'],
            ['name' => 'Nusrat Jahan',   'gender' => 'female', 'class' => 'Eight', 'guardian' => 'Kamal Hossain'],
            ['name' => 'Imran Hossain',  'gender' => 'male',   'class' => 'Eight', 'guardian' => 'Selim Hossain'],
        ];
        foreach ($studentRows as $i => $row) {
            $seq = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $student = Student::firstOrCreate(
                ['school_id' => $sid, 'admission_number' => "ADM-{$year->year}-{$seq}"],
                [
                    'student_id' => "STD-{$year->year}-{$seq}",
                    'name'       => $row['name'],
                    'gender'     => $row['gender'],
                    'status'     => 'active',
                ],
            );

            $section = $sections[$row['class']];
            StudentAcademic::firstOrCreate(
                ['school_id' => $sid, 'student_id' => $student->id, 'academic_year_id' => $year->id],
                [
                    'class_id'    => $section->class_id,
                    'section_id'  => $section->id,
                    'roll_number' => (string) ($i + 1),
                    'is_current'  => true,
                ],
            );

            StudentGuardian::firstOrCreate(
                ['school_id' => $sid, 'student_id' => $student->id, 'relation' => 'father'],
                ['name' => $row['guardian'], 'phone' => '017' . str_pad((string) (10000000 + $i), 8, '0'), 'is_primary' => true],
            );
        }

        // ── Notices ─────────────────────────────────────────────────────────
        if ($admin) {
            foreach ([
                ['title' => 'Admission open for the new academic year', 'body' => 'Applications for classes Six to Eight are now open. Apply online or visit the school office.', 'is_pinned' => true],
                ['title' => 'Annual sports day on Friday', 'body' => 'The annual sports day will be held this Friday on the school ground. All students must attend.', 'is_pinned' => false],
                ['title' => 'Half-yearly examination schedule published', 'body' => 'The half-yearly examination routine has been published. Collect it from your class teacher.', 'is_pinned' => false],
            ] as $n) {
                Announcement::firstOrCreate(
                    ['school_id' => $sid, 'title' => $n['title']],
                    [
                        'created_by' => $admin->id,
                        'body'       => $n['body'],
                        'audience'   => 'all',
                        'is_pinned'  => $n['is_pinned'],
                        'publish_at' => now()->subDays(2),
                    ],
                );
            }
        }
    }
}
