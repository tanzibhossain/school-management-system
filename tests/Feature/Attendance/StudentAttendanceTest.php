<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Attendance\Models\Holiday;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;
    private AcademicYear $year;
    private SchoolClass $class;
    private Section $section;
    private Student $student;
    private Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        // UTC keeps date logic deterministic in tests
        $this->school = School::create(['name' => 'Test School', 'timezone' => 'UTC', 'is_active' => true]);

        // All 7 days open, closing 16:00 — weekend behaviour is tested explicitly below
        foreach (range(0, 6) as $day) {
            SchoolOpeningHour::create([
                'school_id'   => $this->school->id,
                'day_of_week' => $day,
                'is_open'     => true,
                'open_time'   => '08:00:00',
                'close_time'  => '16:00:00',
            ]);
        }

        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');

        $this->year = AcademicYear::create([
            'school_id'  => $this->school->id,
            'year'       => '2026',
            'is_current' => true,
        ]);

        $this->class   = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 5']);
        $this->section = Section::create(['school_id' => $this->school->id, 'class_id' => $this->class->id, 'name' => 'A']);

        $this->student  = $this->makeStudent('ADM-001');
        $this->student2 = $this->makeStudent('ADM-002');
    }

    private function makeStudent(string $admissionNumber): Student
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        return Student::create([
            'school_id'        => $this->school->id,
            'user_id'          => $user->id,
            'admission_number' => $admissionNumber,
            'name'             => "Student {$admissionNumber}",
            'gender'           => 'male',
            'status'           => 'active',
        ]);
    }

    private function adminToken(): string
    {
        return $this->admin->createToken('test', ['*'])->plainTextToken;
    }

    private function teacherToken(?int $classTeacherOfSection = null): string
    {
        $user = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);

        $staff = Staff::create([
            'school_id' => $this->school->id,
            'user_id'   => $user->id,
            'name'      => 'Teacher One',
            'gender'    => 'female',
        ]);

        if ($classTeacherOfSection !== null) {
            Section::whereKey($classTeacherOfSection)->update(['class_teacher_id' => $staff->id]);
        }

        return $user->createToken('test', ['teacher:*'])->plainTextToken;
    }

    private function bulkPayload(string $date, string $status = 'present'): array
    {
        return [
            'class_id'   => $this->class->id,
            'section_id' => $this->section->id,
            'date'       => $date,
            'entries'    => [
                ['student_id' => $this->student->id,  'status' => $status],
                ['student_id' => $this->student2->id, 'status' => 'absent'],
            ],
        ];
    }

    private function yesterday(): string
    {
        return CarbonImmutable::now('UTC')->subDay()->toDateString();
    }

    // ── Bulk upsert ──────────────────────────────────────────────────────────

    public function test_bulk_creates_records_and_resubmission_updates_without_duplicates(): void
    {
        $token = $this->adminToken();
        $date  = $this->yesterday();

        $this->withToken($token)
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($date))
            ->assertCreated()
            ->assertJsonFragment(['created' => 2, 'updated' => 0]);

        // Resubmit with a correction — updates, never errors or duplicates
        $this->withToken($token)
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($date, 'late'))
            ->assertCreated()
            ->assertJsonFragment(['created' => 0, 'updated' => 2]);

        $this->assertDatabaseCount('student_attendances', 2);
        $this->assertDatabaseHas('student_attendances', [
            'student_id' => $this->student->id,
            'status'     => 'late',
            'edited_by'  => $this->admin->id,
        ]);
    }

    public function test_attendance_rejected_on_holiday(): void
    {
        $date = $this->yesterday();

        Holiday::create([
            'school_id' => $this->school->id,
            'date'      => $date,
            'name'      => 'Sudden Closure',
            'type'      => 'closure',
        ]);

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($date))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_attendance_rejected_on_closed_weekday(): void
    {
        $date = $this->yesterday();

        SchoolOpeningHour::where('school_id', $this->school->id)
            ->where('day_of_week', CarbonImmutable::parse($date)->dayOfWeek)
            ->update(['is_open' => false]);

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($date))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date');
    }

    public function test_future_date_rejected_for_non_admin(): void
    {
        $token  = $this->teacherToken($this->section->id);
        $future = CarbonImmutable::now('UTC')->addDays(2)->toDateString();

        $this->withToken($token)
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($future))
            ->assertUnprocessable();
    }

    // ── Permissions & edit window ────────────────────────────────────────────

    public function test_class_teacher_can_record_own_section(): void
    {
        $token = $this->teacherToken($this->section->id);

        $this->withToken($token)
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($this->yesterday()))
            ->assertCreated();
    }

    public function test_other_teacher_cannot_record_section(): void
    {
        $token = $this->teacherToken(null); // teacher, but not this section's class teacher

        $this->withToken($token)
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($this->yesterday()))
            ->assertForbidden();
    }

    public function test_teacher_blocked_outside_edit_window_but_admin_allowed(): void
    {
        $oldDate = CarbonImmutable::now('UTC')->subDays(30)->toDateString();

        $this->withToken($this->teacherToken($this->section->id))
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($oldDate))
            ->assertForbidden();

        // Sanctum's guard caches the resolved user within a test — reset before switching tokens
        $this->app['auth']->forgetGuards();

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($oldDate))
            ->assertCreated();
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($this->yesterday()))
            ->assertUnauthorized();
    }

    // ── Register & summary ───────────────────────────────────────────────────

    public function test_register_returns_day_records(): void
    {
        $token = $this->adminToken();
        $date  = $this->yesterday();

        $this->withToken($token)->postJson('/api/v2/attendance/students/bulk', $this->bulkPayload($date));

        $this->withToken($token)
            ->getJson("/api/v2/attendance/students/register?class_id={$this->class->id}&section_id={$this->section->id}&date={$date}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_summary_excludes_holidays_from_working_days(): void
    {
        $token = $this->adminToken();

        // Fixed past week: Mon 2026-01-05 … Fri 2026-01-09, holiday on Wed 07
        Holiday::create([
            'school_id' => $this->school->id,
            'date'      => '2026-01-07',
            'name'      => 'Holiday',
        ]);

        // Enrollment clamp uses created_at — backdate the student
        $this->student->created_at = '2026-01-01 00:00:00';
        $this->student->save();

        foreach ([
            '2026-01-05' => 'present',
            '2026-01-06' => 'present',
            '2026-01-08' => 'absent',
            '2026-01-09' => 'leave',
        ] as $date => $status) {
            StudentAttendance::create([
                'school_id'        => $this->school->id,
                'student_id'       => $this->student->id,
                'class_id'         => $this->class->id,
                'section_id'       => $this->section->id,
                'academic_year_id' => $this->year->id,
                'date'             => $date,
                'status'           => $status,
                'recorded_by'      => $this->admin->id,
            ]);
        }

        $this->withToken($token)
            ->getJson("/api/v2/attendance/students/{$this->student->id}/summary?from=2026-01-05&to=2026-01-09")
            ->assertOk()
            ->assertJsonFragment([
                'working_days' => 4,   // 5 days minus the holiday
                'present'      => 2,
                'absent'       => 1,
                'leave'        => 1,
                'percentage'   => 50.0, // 2 attended / 4 working days
            ]);
    }
}
