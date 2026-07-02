<?php

namespace Tests\Feature\Mark;

use App\Modules\Examination\Models\Exam;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Mark\Models\MarkSetting;

class ResultCalculationTest extends MarkTestCase
{
    private function calculate(): void
    {
        $this->withToken($this->adminToken())
            ->postJson("/api/v2/marks/results/{$this->exam->id}/calculate")
            ->assertCreated();
    }

    private function resultOf(int $studentId): ExamResult
    {
        return ExamResult::where('exam_id', $this->exam->id)
            ->where('student_id', $studentId)
            ->firstOrFail();
    }

    // ── bd_national strategy ─────────────────────────────────────────────────

    public function test_bd_national_gpa_with_optional_bonus_and_cap(): void
    {
        // Math 85 (A+ 5.00), English 75 (A 4.00), optional Music 90 (A+ 5.00)
        // base = (5+4)/2 = 4.5; bonus = 5−2 = 3 → (9+3)/2 = 6 → capped at 5.00
        $this->giveMarks($this->student, 'Math', 85);
        $this->giveMarks($this->student, 'English', 75);
        $this->giveMarks($this->student, 'Music', 90);

        $this->calculate();

        $result = $this->resultOf($this->student->id);
        $this->assertTrue($result->is_pass);
        $this->assertSame('5.00', (string) $result->gpa);
        $this->assertSame('A+', $result->grade);
    }

    public function test_bd_national_fail_one_fail_all(): void
    {
        $this->giveMarks($this->student, 'Math', 85);
        $this->giveMarks($this->student, 'English', 20); // below 33 → fail
        $this->giveMarks($this->student, 'Music', 90);

        $this->calculate();

        $result = $this->resultOf($this->student->id);
        $this->assertFalse($result->is_pass);
        $this->assertSame('0.00', (string) $result->gpa);
        $this->assertSame('F', $result->grade);
    }

    public function test_absent_subject_shows_ab_and_fails_student(): void
    {
        $this->giveMarks($this->student, 'Math', 85);
        $this->giveMarks($this->student, 'English', 60, absent: true);
        $this->giveMarks($this->student, 'Music', 90);

        $this->calculate();

        $result = $this->resultOf($this->student->id);
        $this->assertFalse($result->is_pass);

        $english = collect($result->subject_breakdown)->firstWhere('subject_name', 'English');
        $this->assertTrue($english['is_absent']);
        $this->assertSame('Ab', $english['display_mark']);
    }

    public function test_non_enrolled_subject_is_na_and_excluded(): void
    {
        // student2 is not enrolled in Music and has no Music marks
        $this->giveMarks($this->student2, 'Math', 70);
        $this->giveMarks($this->student2, 'English', 70);

        $this->calculate();

        $result = $this->resultOf($this->student2->id);
        $this->assertTrue($result->is_pass);
        $this->assertSame('4.00', (string) $result->gpa); // (4+4)/2, Music excluded

        $music = collect($result->subject_breakdown)->firstWhere('subject_name', 'Music');
        $this->assertTrue($music['not_applicable']);
        $this->assertSame('N/A', $music['display_mark']);
    }

    // ── Combined subjects ────────────────────────────────────────────────────

    public function test_combined_subjects_graded_as_one_with_combined_pass_mark(): void
    {
        $b1 = $this->addSubjectToExam('Bangla 1st', $this->exam, combinedGroup: 1);
        $b2 = $this->addSubjectToExam('Bangla 2nd', $this->exam, combinedGroup: 1);

        $this->enroll($this->student, ['Bangla 1st' => false, 'Bangla 2nd' => false]);

        $this->giveMarks($this->student, 'Math', 80);
        $this->giveMarks($this->student, 'English', 80);
        $this->giveMarks($this->student, 'Music', 80);
        // B1 = 30 (below its own 33) + B2 = 40 → combined 70 ≥ combined pass 66 → PASSES as one unit
        $this->giveMarks($this->student, 'Bangla 1st', 30);
        $this->giveMarks($this->student, 'Bangla 2nd', 40);

        $this->calculate();

        $result = $this->resultOf($this->student->id);
        $this->assertTrue($result->is_pass, 'Combined total 70/200 >= combined pass 66 must pass');

        $combined = collect($result->subject_breakdown)->firstWhere('subject_name', 'Bangla 1st + Bangla 2nd');
        $this->assertNotNull($combined);
        $this->assertTrue($combined['is_pass']);
        $this->assertEquals(70.0, (float) $combined['obtained']);
        $this->assertEquals(200.0, (float) $combined['possible']);
    }

    // ── Merit positions ──────────────────────────────────────────────────────

    public function test_merit_positions_with_ties_and_failed_ranked_last(): void
    {
        // student3 & student4: identical enrollment (Math+English) for a true tie
        $student3 = $this->makeStudent('ADM-003');
        $student4 = $this->makeStudent('ADM-004');
        $this->enroll($student3, ['Math' => false, 'English' => false]);
        $this->enroll($student4, ['Math' => false, 'English' => false]);

        // Top student: highest total
        $this->giveMarks($this->student, 'Math', 90);
        $this->giveMarks($this->student, 'English', 90);
        $this->giveMarks($this->student, 'Music', 90);

        // Identical tuples → tie
        foreach ([$student3, $student4] as $s) {
            $this->giveMarks($s, 'Math', 80);
            $this->giveMarks($s, 'English', 80);
        }

        // student2 fails → ranked last despite a high Math score
        $this->giveMarks($this->student2, 'Math', 90);
        $this->giveMarks($this->student2, 'English', 10);

        $this->calculate();

        $this->assertSame(1, $this->resultOf($this->student->id)->merit_position);
        $this->assertSame(2, $this->resultOf($student3->id)->merit_position);
        $this->assertSame(2, $this->resultOf($student4->id)->merit_position);
        $this->assertSame(4, $this->resultOf($this->student2->id)->merit_position);
    }

    public function test_merit_hidden_from_teacher_when_toggle_off_but_visible_to_admin(): void
    {
        MarkSetting::forClass($this->school->id, $this->class->id)
            ->update(['show_merit_position' => false]);

        $this->giveMarks($this->student, 'Math', 80);
        $this->giveMarks($this->student, 'English', 80);
        $this->calculate();

        // Teacher view: merit hidden (reset guard — calculate() ran as admin)
        $teacher = \App\Models\User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $teacherToken = $teacher->createToken('test', ['teacher:*'])->plainTextToken;

        $this->app['auth']->forgetGuards();

        $response = $this->withToken($teacherToken)
            ->getJson("/api/v2/marks/results/{$this->exam->id}/tabulation")
            ->assertOk();

        $this->assertNull($response->json('data.0.merit_position'));

        // Admin view: merit visible
        $this->app['auth']->forgetGuards();

        $response = $this->withToken($this->adminToken())
            ->getJson("/api/v2/marks/results/{$this->exam->id}/tabulation")
            ->assertOk();

        $this->assertNotNull($response->json('data.0.merit_position'));
    }

    // ── Locking ──────────────────────────────────────────────────────────────

    public function test_lock_freezes_results_and_marks(): void
    {
        $this->giveMarks($this->student, 'Math', 80);
        $this->giveMarks($this->student, 'English', 80);
        $this->calculate();

        $token = $this->adminToken();

        $this->withToken($token)
            ->postJson("/api/v2/marks/results/{$this->exam->id}/lock")
            ->assertOk();

        // Mark entry now rejected
        $this->withToken($token)->postJson('/api/v2/marks/enter', [
            'mark_division_id' => $this->divisions['Math']['mid']->id,
            'entries'          => [['student_id' => $this->student->id, 'marks_obtained' => 5]],
        ])->assertUnprocessable();

        // Recalculation writes nothing for locked results
        $this->withToken($token)
            ->postJson("/api/v2/marks/results/{$this->exam->id}/calculate")
            ->assertCreated()
            ->assertJsonFragment(['results_written' => 0]);
    }

    // ── Student access ───────────────────────────────────────────────────────

    public function test_student_sees_own_result_but_not_others(): void
    {
        $this->giveMarks($this->student, 'Math', 80);
        $this->giveMarks($this->student, 'English', 80);
        $this->giveMarks($this->student2, 'Math', 70);
        $this->giveMarks($this->student2, 'English', 70);
        $this->calculate();

        $studentToken = $this->student->user->createToken('test', ['student:*'])->plainTextToken;

        $this->withToken($studentToken)
            ->getJson("/api/v2/marks/results/student/{$this->student->id}?exam_id={$this->exam->id}")
            ->assertOk()
            ->assertJsonFragment(['student_id' => $this->student->id]);

        $this->app['auth']->forgetGuards();

        $this->withToken($studentToken)
            ->getJson("/api/v2/marks/results/student/{$this->student2->id}?exam_id={$this->exam->id}")
            ->assertForbidden();
    }

    // ── Weighted annual result ───────────────────────────────────────────────

    public function test_weighted_annual_result(): void
    {
        // Exam 1 (this fixture): 80% across all three enrolled subjects
        // (student is enrolled in optional Music — leaving it unmarked would
        //  drag exam 1 down to 160/300 and skew the weighted expectation)
        $this->giveMarks($this->student, 'Math', 80);
        $this->giveMarks($this->student, 'English', 80);
        $this->giveMarks($this->student, 'Music', 80);
        $this->calculate();

        // Exam 2: same subjects, student scores 60%
        $exam2 = Exam::create([
            'school_id'        => $this->school->id,
            'exam_type_id'     => $this->exam->exam_type_id,
            'academic_year_id' => $this->year->id,
            'class_id'         => $this->class->id,
            'title'            => 'Annual 2026',
            'start_date'       => '2026-11-01',
            'end_date'         => '2026-11-10',
            'status'           => 'published',
        ]);

        $firstExam = $this->exam;
        $this->exam = $exam2;
        $this->examSubjects = [];
        $this->divisions = [];
        $this->addSubjectToExam('Math', $exam2);
        $this->addSubjectToExam('English', $exam2);
        $this->giveMarks($this->student, 'Math', 60);
        $this->giveMarks($this->student, 'English', 60);

        $token = $this->adminToken();
        $this->withToken($token)->postJson("/api/v2/marks/results/{$exam2->id}/calculate")->assertCreated();

        // Weights: first exam 30%, annual 70%
        $this->withToken($token)->putJson('/api/v2/marks/exam-weights', [
            'class_id'         => $this->class->id,
            'academic_year_id' => $this->year->id,
            'weights'          => [
                ['exam_id' => $firstExam->id, 'weight_percent' => 30],
                ['exam_id' => $exam2->id, 'weight_percent' => 70],
            ],
        ])->assertCreated();

        $response = $this->withToken($token)
            ->getJson("/api/v2/marks/results/annual?class_id={$this->class->id}&academic_year_id={$this->year->id}")
            ->assertOk();

        $row = collect($response->json('data'))->firstWhere('student_id', $this->student->id);

        // 80*0.3 + 60*0.7 = 66.00
        $this->assertEquals(66.0, (float) $row['weighted_percentage']);
        $this->assertTrue($row['is_pass']);
    }

    public function test_weights_must_sum_to_100(): void
    {
        $this->withToken($this->adminToken())->putJson('/api/v2/marks/exam-weights', [
            'class_id'         => $this->class->id,
            'academic_year_id' => $this->year->id,
            'weights'          => [
                ['exam_id' => $this->exam->id, 'weight_percent' => 60],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('weights');
    }
}
