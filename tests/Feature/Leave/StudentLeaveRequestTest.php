<?php

namespace Tests\Feature\Leave;

use App\Modules\Attendance\Models\Holiday;
use App\Modules\Attendance\Models\StudentAttendance;
use App\Modules\School\Models\SchoolOpeningHour;

class StudentLeaveRequestTest extends LeaveTestCase
{
    // Fixed past week: Mon 2026-01-05 … Fri 2026-01-09 (see Attendance tests for the same anchor week).
    private const FROM = '2026-01-05';

    private const TO = '2026-01-09';

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'leave_type_id' => $this->studentLeaveType->id,
            'from_date'     => self::FROM,
            'to_date'       => self::TO,
            'reason'        => 'Family emergency',
        ], $overrides);
    }

    public function test_submit_counts_working_days_excluding_holiday_and_closed_weekday(): void
    {
        // Wed 07 is a holiday; Fri 09 is a closed weekday — only Mon/Tue/Thu remain (3 working days).
        Holiday::create(['school_id' => $this->school->id, 'date' => '2026-01-07', 'name' => 'Holiday']);
        SchoolOpeningHour::create([
            'school_id' => $this->school->id, 'day_of_week' => 5, 'is_open' => false,
            'open_time' => '08:00:00', 'close_time' => '16:00:00',
        ]);

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/leave/students/{$this->student->id}", $this->payload())
            ->assertCreated()
            ->assertJsonFragment(['working_days' => 3, 'status' => 'pending']);
    }

    public function test_submission_rejected_when_range_has_no_working_days(): void
    {
        // Single-day request that happens to be a closed weekday.
        SchoolOpeningHour::create([
            'school_id' => $this->school->id, 'day_of_week' => 1, 'is_open' => false,
            'open_time' => '08:00:00', 'close_time' => '16:00:00',
        ]);

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/leave/students/{$this->student->id}", $this->payload([
                'from_date' => '2026-01-05', 'to_date' => '2026-01-05',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from_date');
    }

    public function test_submission_rejected_when_type_requires_attachment_and_none_given(): void
    {
        $this->studentLeaveType->update(['requires_attachment' => true]);

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/leave/students/{$this->student->id}", $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment');
    }

    public function test_submission_rejected_when_exceeding_yearly_balance(): void
    {
        // Tighten this type's limit to 3 days; approve a 2-day request, then a further 2-day request must fail.
        $this->studentLeaveType->update(['max_days_per_year' => 3]);
        $this->approveRequest($this->submitAndGetId('2026-01-05', '2026-01-06')); // 2 working days used

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/leave/students/{$this->student->id}", $this->payload([
                'from_date' => '2026-01-08', 'to_date' => '2026-01-09', // 2 more working days — total 4 > 3
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('leave_type_id');
    }

    public function test_class_teacher_can_approve_and_attendance_is_synced_correctly(): void
    {
        // Pre-existing attendance for part of the range — approval must only override 'absent'.
        $this->markAttendance('2026-01-05', 'present');
        $this->markAttendance('2026-01-06', 'absent');
        $this->markAttendance('2026-01-09', 'late');
        // 2026-01-08 has no existing record — should be created as 'leave'.

        $requestId = $this->submitAndGetId(self::FROM, self::TO);

        $teacherToken = $this->teacherToken($this->section->id);

        // Sanctum's guard caches the resolved user within a test — reset before switching tokens
        $this->app['auth']->forgetGuards();

        $this->withToken($teacherToken)
            ->patchJson("/api/v2/leave/students/{$requestId}/approve")
            ->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        // 'date' is cast to a Carbon date, but SQLite stores it as a full 'Y-m-d H:i:s' string
        $this->assertDatabaseHas('student_attendances', ['student_id' => $this->student->id, 'date' => '2026-01-05 00:00:00', 'status' => 'present']);
        $this->assertDatabaseHas('student_attendances', ['student_id' => $this->student->id, 'date' => '2026-01-06 00:00:00', 'status' => 'leave']);
        $this->assertDatabaseHas('student_attendances', ['student_id' => $this->student->id, 'date' => '2026-01-08 00:00:00', 'status' => 'leave']);
        $this->assertDatabaseHas('student_attendances', ['student_id' => $this->student->id, 'date' => '2026-01-09 00:00:00', 'status' => 'late']);
    }

    public function test_other_teacher_cannot_approve(): void
    {
        $requestId = $this->submitAndGetId(self::FROM, self::TO);
        $otherTeacherToken = $this->teacherToken(null); // not this section's class teacher

        // Sanctum's guard caches the resolved user within a test — reset before switching tokens
        $this->app['auth']->forgetGuards();

        $this->withToken($otherTeacherToken)
            ->patchJson("/api/v2/leave/students/{$requestId}/approve")
            ->assertForbidden();
    }

    public function test_admin_can_reject_with_reason(): void
    {
        $requestId = $this->submitAndGetId(self::FROM, self::TO);

        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/leave/students/{$requestId}/reject", ['rejection_reason' => 'Not enough notice'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'rejected', 'rejection_reason' => 'Not enough notice']);
    }

    public function test_requester_can_cancel_while_pending(): void
    {
        $token = $this->adminToken();
        $requestId = $this->submitAndGetId(self::FROM, self::TO, $token);

        $this->withToken($token)
            ->patchJson("/api/v2/leave/students/{$requestId}/cancel")
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelled']);
    }

    public function test_requires_auth(): void
    {
        $this->postJson("/api/v2/leave/students/{$this->student->id}", $this->payload())
            ->assertUnauthorized();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function submitAndGetId(string $from, string $to, ?string $token = null): int
    {
        $response = $this->withToken($token ?? $this->adminToken())
            ->postJson("/api/v2/leave/students/{$this->student->id}", $this->payload([
                'from_date' => $from, 'to_date' => $to,
            ]))
            ->assertCreated();

        return $response->json('data.id');
    }

    private function approveRequest(int $id): void
    {
        $this->withToken($this->adminToken())
            ->patchJson("/api/v2/leave/students/{$id}/approve")
            ->assertOk();
    }

    private function markAttendance(string $date, string $status): void
    {
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
}
