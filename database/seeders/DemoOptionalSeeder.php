<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Subject;
use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BorrowRecord;
use App\Modules\Library\Models\LibraryMember;
use App\Modules\Lms\Models\Assignment;
use App\Modules\Lms\Models\Course;
use App\Modules\Lms\Models\Lesson;
use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\Payroll\Models\StaffSalaryValue;
use App\Modules\School\Models\School;
use App\Modules\School\Services\ModuleSettingService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Transport\Models\StudentTransportAssignment;
use App\Modules\Transport\Models\TransportDriver;
use App\Modules\Transport\Models\TransportRoute;
use App\Modules\Transport\Models\TransportVehicle;
use Illuminate\Database\Seeder;

/**
 * Optional-module demo data. Enables the optional modules and seeds Library,
 * Transport, Payroll, and LMS so every module is walkable. Run last.
 */
class DemoOptionalSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();
        if (! $school) {
            return;
        }
        $sid = $school->id;

        // ── Enable all optional modules ─────────────────────────────────────
        $modules = app(ModuleSettingService::class);
        foreach (['library', 'transport', 'payroll', 'lms', 'messaging'] as $m) {
            $modules->setEnabled($sid, $m, true);
        }

        // ── Library ─────────────────────────────────────────────────────────
        $books = [
            ['title' => 'Bangla Byakaran', 'author' => 'Dr. Hayat Mahmud', 'category' => 'Language', 'copies' => 8],
            ['title' => 'Essential English Grammar', 'author' => 'Raymond Murphy', 'category' => 'Language', 'copies' => 6],
            ['title' => 'Secondary Mathematics', 'author' => 'NCTB', 'category' => 'Mathematics', 'copies' => 10],
            ['title' => 'General Science', 'author' => 'NCTB', 'category' => 'Science', 'copies' => 10],
            ['title' => 'Bangladesh & Global Studies', 'author' => 'NCTB', 'category' => 'Social Science', 'copies' => 7],
        ];
        $firstBook = null;
        foreach ($books as $i => $b) {
            $book = Book::firstOrCreate(
                ['school_id' => $sid, 'title' => $b['title']],
                [
                    'author' => $b['author'], 'category' => $b['category'], 'publisher' => 'NCTB',
                    'isbn' => '978-984-' . str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                    'total_copies' => $b['copies'], 'available_copies' => $b['copies'],
                    'is_active' => true, 'is_trash' => false,
                ],
            );
            $firstBook = $firstBook ?? $book;
        }

        // Members need a user account — use the demo teacher/student users if present.
        $studentUser = User::where('school_id', $sid)->where('email', 'student@school.edu.bd')->first();
        $teacherUser = User::where('school_id', $sid)->where('email', 'teacher@school.edu.bd')->first();
        $memberSeq = 1;
        $studentMember = null;
        foreach ([['user' => $studentUser, 'type' => 'student'], ['user' => $teacherUser, 'type' => 'staff']] as $mm) {
            if (! $mm['user']) {
                continue;
            }
            $member = LibraryMember::firstOrCreate(
                ['school_id' => $sid, 'user_id' => $mm['user']->id],
                [
                    'member_type' => $mm['type'],
                    'membership_number' => 'LIB-' . str_pad((string) $memberSeq, 4, '0', STR_PAD_LEFT),
                    'joined_at' => now()->subMonths(2)->toDateString(),
                    'is_active' => true, 'is_trash' => false,
                ],
            );
            if ($mm['type'] === 'student') {
                $studentMember = $member;
            }
            $memberSeq++;
        }

        // One active borrow.
        if ($studentMember && $firstBook && $firstBook->available_copies > 0) {
            $exists = BorrowRecord::where('school_id', $sid)
                ->where('library_member_id', $studentMember->id)->where('book_id', $firstBook->id)->exists();
            if (! $exists) {
                BorrowRecord::create([
                    'school_id' => $sid, 'library_member_id' => $studentMember->id, 'book_id' => $firstBook->id,
                    'borrowed_at' => now()->subDays(5), 'due_at' => now()->addDays(9), 'status' => 'borrowed',
                ]);
                $firstBook->decrement('available_copies');
            }
        }

        // ── Transport ───────────────────────────────────────────────────────
        $driver = TransportDriver::firstOrCreate(
            ['school_id' => $sid, 'license_no' => 'DL-DHK-0001'],
            ['name' => 'Abul Kashem', 'phone' => '01812345678', 'status' => 'active'],
        );
        $vehicle = TransportVehicle::firstOrCreate(
            ['school_id' => $sid, 'registration_no' => 'DHAKA-METRO-GA-11-1234'],
            ['capacity' => 30, 'status' => 'in_service', 'notes' => 'School bus'],
        );
        $route = TransportRoute::firstOrCreate(
            ['school_id' => $sid, 'name' => 'Route 1 — Town Centre'],
            [
                'description' => 'Town Centre ↔ School via Main Road',
                'fare' => 500, 'current_vehicle_id' => $vehicle->id, 'driver_id' => $driver->id, 'is_active' => true,
            ],
        );
        $riderStudent = Student::where('school_id', $sid)->orderBy('id')->first();
        if ($riderStudent) {
            StudentTransportAssignment::firstOrCreate(
                ['school_id' => $sid, 'student_id' => $riderStudent->id, 'transport_route_id' => $route->id],
                ['pickup_point' => 'Town Centre', 'starts_on' => now()->startOfMonth()->toDateString(), 'status' => 'active'],
            );
        }

        // ── Payroll ─────────────────────────────────────────────────────────
        $components = [
            ['name' => 'Basic Salary',    'type' => 'earning',   'order' => 1],
            ['name' => 'House Rent',      'type' => 'earning',   'order' => 2],
            ['name' => 'Medical Allowance', 'type' => 'earning', 'order' => 3],
            ['name' => 'Provident Fund',  'type' => 'deduction', 'order' => 4],
            ['name' => 'Income Tax',      'type' => 'deduction', 'order' => 5],
        ];
        $componentModels = [];
        foreach ($components as $c) {
            $componentModels[$c['name']] = SalaryComponent::firstOrCreate(
                ['school_id' => $sid, 'name' => $c['name']],
                ['component_type' => $c['type'], 'is_default' => true, 'sort_order' => $c['order'], 'is_trash' => false],
            );
        }
        // Salary values for the teaching staff.
        $values = ['Basic Salary' => 20000, 'House Rent' => 8000, 'Medical Allowance' => 2000, 'Provident Fund' => 1500, 'Income Tax' => 500];
        foreach (Staff::where('school_id', $sid)->whereNotNull('subject_id')->get() as $staff) {
            foreach ($values as $compName => $amount) {
                StaffSalaryValue::firstOrCreate(
                    ['school_id' => $sid, 'staff_id' => $staff->id, 'salary_component_id' => $componentModels[$compName]->id],
                    ['amount' => $amount],
                );
            }
        }

        // ── LMS ─────────────────────────────────────────────────────────────
        $class8 = SchoolClass::where('school_id', $sid)->where('name', 'Class 8')->first();
        $mathSubject = Subject::where('school_id', $sid)->where('name', 'Mathematics')->first();
        $mathTeacher = Staff::where('school_id', $sid)->where('subject_id', optional($mathSubject)->id)->first();
        if ($class8 && $mathSubject) {
            $course = Course::firstOrCreate(
                ['school_id' => $sid, 'class_id' => $class8->id, 'subject_id' => $mathSubject->id, 'title' => 'Class 8 Mathematics'],
                ['teacher_id' => optional($mathTeacher)->id, 'description' => 'Algebra, geometry and arithmetic for Class 8.', 'is_active' => true],
            );
            foreach ([
                ['title' => 'Introduction to Algebra', 'order' => 1],
                ['title' => 'Linear Equations', 'order' => 2],
                ['title' => 'Geometry Basics', 'order' => 3],
            ] as $l) {
                Lesson::firstOrCreate(
                    ['school_id' => $sid, 'course_id' => $course->id, 'title' => $l['title']],
                    ['content_type' => 'text', 'body_text' => 'Lesson notes for ' . $l['title'] . '.', 'sort_order' => $l['order'], 'is_published' => true],
                );
            }
            Assignment::firstOrCreate(
                ['school_id' => $sid, 'course_id' => $course->id, 'title' => 'Algebra Worksheet 1'],
                ['instructions' => 'Solve problems 1–10 from the algebra worksheet.', 'due_date' => now()->addDays(7)->toDateString(), 'max_marks' => 20, 'allow_late_submission' => true],
            );
        }
    }
}
