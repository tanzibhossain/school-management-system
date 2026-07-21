<?php

namespace Tests\Feature\Mark;

use App\Models\User;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Models\RoutinePeriod;
use App\Modules\Academic\Models\RoutineRoom;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\Mark;
use App\Modules\Mark\Models\MarkDivision;
use App\Modules\Staff\Models\Staff;

class MarkEntryTest extends MarkTestCase
{
    private function entryPayload(float $marks = 30): array
    {
        return [
            'mark_division_id' => $this->divisions['Math']['mid']->id,
            'entries' => [
                ['student_id' => $this->student->id, 'marks_obtained' => $marks],
            ],
        ];
    }

    public function test_bulk_entry_creates_then_updates(): void
    {
        $token = $this->adminToken();

        $this->withToken($token)->postJson('/api/v2/marks/enter', $this->entryPayload(30))
            ->assertCreated()
            ->assertJsonFragment(['created' => 1, 'updated' => 0]);

        $this->withToken($token)->postJson('/api/v2/marks/enter', $this->entryPayload(35))
            ->assertCreated()
            ->assertJsonFragment(['created' => 0, 'updated' => 1]);

        $this->assertDatabaseCount('marks', 1);
        $this->assertDatabaseHas('marks', ['student_id' => $this->student->id, 'marks_obtained' => 35]);
    }

    public function test_marks_above_division_max_rejected(): void
    {
        // Math Mid max = 40
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/marks/enter', $this->entryPayload(45))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entries');
    }

    public function test_absent_entry_stored_without_marks(): void
    {
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/marks/enter', [
                'mark_division_id' => $this->divisions['Math']['mid']->id,
                'entries' => [['student_id' => $this->student->id, 'is_absent' => true]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('marks', [
            'student_id' => $this->student->id,
            'is_absent' => true,
            'marks_obtained' => null,
        ]);
    }

    public function test_locked_mark_rejects_changes(): void
    {
        Mark::create([
            'school_id' => $this->school->id,
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'mark_division_id' => $this->divisions['Math']['mid']->id,
            'marks_obtained' => 20,
            'entered_by' => $this->admin->id,
            'locked_at' => now(),
        ]);

        $this->withToken($this->adminToken())
            ->postJson('/api/v2/marks/enter', $this->entryPayload(30))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('entries');
    }

    public function test_teacher_can_enter_only_assigned_subjects(): void
    {
        $teacherUser = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $staff = Staff::create([
            'school_id' => $this->school->id, 'user_id' => $teacherUser->id,
            'name' => 'Math Teacher', 'gender' => 'female',
        ]);
        $token = $teacherUser->createToken('test', ['teacher:*'])->plainTextToken;

        // Not assigned to Math yet → forbidden
        $this->withToken($token)->postJson('/api/v2/marks/enter', $this->entryPayload(30))
            ->assertForbidden();

        // Assign via class routine, reset the cached guard, retry → allowed
        $room = RoutineRoom::create(['school_id' => $this->school->id, 'name' => 'R1']);
        $period = RoutinePeriod::create(['school_id' => $this->school->id, 'name' => 'P1', 'start_time' => '10:00', 'end_time' => '11:00']);

        ClassRoutine::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $this->examSubjects['Math']->subjectRelation->subject_id,
            'teacher_id' => $staff->id,
            'room_id' => $room->id,
            'period_id' => $period->id,
            'day_of_week' => 'monday',
        ]);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->postJson('/api/v2/marks/enter', $this->entryPayload(30))
            ->assertCreated();
    }

    public function test_grace_within_cap_applied_and_audited(): void
    {
        $token = $this->adminToken();
        $this->withToken($token)->postJson('/api/v2/marks/enter', $this->entryPayload(10))->assertCreated();

        $mark = Mark::firstOrFail();

        $this->withToken($token)
            ->postJson("/api/v2/marks/{$mark->id}/grace", ['grace_marks' => 3])
            ->assertOk()
            ->assertJsonFragment(['grace_marks' => '3.00']);

        $this->assertDatabaseHas('marks', [
            'id' => $mark->id,
            'grace_marks' => 3,
            'grace_given_by' => $this->admin->id,
            'marks_obtained' => 10, // raw mark untouched — grace is separate
        ]);
    }

    public function test_grace_above_cap_rejected(): void
    {
        $token = $this->adminToken();
        $this->withToken($token)->postJson('/api/v2/marks/enter', $this->entryPayload(10))->assertCreated();

        $mark = Mark::firstOrFail();

        // Default cap is 5.00
        $this->withToken($token)
            ->postJson("/api/v2/marks/{$mark->id}/grace", ['grace_marks' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grace_marks');
    }

    public function test_grade_template_seeds_boundaries(): void
    {
        // setUp applied bd_national_5 → 7 rows
        $this->assertSame(7, GradeBoundary::forClass($this->school->id, $this->class->id)->count());

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/marks/grade-boundaries/{$this->class->id}/apply-template", ['template' => 'us_letter_4'])
            ->assertCreated()
            ->assertJsonFragment(['boundaries_created' => 5]);

        $this->assertSame(5, GradeBoundary::forClass($this->school->id, $this->class->id)->count());
    }

    public function test_division_template_splits_full_marks(): void
    {
        // Music has no marks yet — replace its divisions with the 'standard' template
        $examSubject = $this->examSubjects['Music'];

        $this->withToken($this->adminToken())
            ->postJson("/api/v2/marks/divisions/{$examSubject->id}/apply-template", ['template' => 'standard'])
            ->assertCreated()
            ->assertJsonFragment(['divisions_created' => 3]);

        $divisions = MarkDivision::where('exam_subject_id', $examSubject->id)->orderBy('display_order')->get();

        $this->assertSame(['Attendance', 'Mid Term', 'Final'], $divisions->pluck('name')->all());
        $this->assertEquals([10.0, 30.0, 60.0], $divisions->pluck('max_marks')->map(fn ($m) => (float) $m)->all());
    }

    public function test_unknown_template_rejected(): void
    {
        $this->withToken($this->adminToken())
            ->postJson("/api/v2/marks/grade-boundaries/{$this->class->id}/apply-template", ['template' => 'nope'])
            ->assertUnprocessable();
    }
}
