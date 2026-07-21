<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Subject;
use App\Modules\LMS\Models\Assignment;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Modules\LMS\Models\Submission;
use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Blade admin — LMS optional module (gating, courses, lessons, assignments, grading).
 */
class LmsModuleTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private SchoolClass $class;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->school = School::create([
            'name' => 'Test School', 'is_active' => true, 'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka', 'locale' => 'en', 'academic_year_pattern' => 'jan_dec',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id, 'is_active' => true]);
        $this->admin->assignRole('admin');
        $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class 6']);
        $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Maths']);
    }

    private function enable(): void
    {
        ModuleSetting::create(['school_id' => $this->school->id, 'module' => 'lms', 'is_enabled' => true]);
    }

    private function makeCourse(): Course
    {
        return Course::create([
            'school_id' => $this->school->id, 'class_id' => $this->class->id, 'subject_id' => $this->subject->id,
            'title' => 'Algebra Basics', 'is_active' => true,
        ]);
    }

    public function test_403_when_disabled(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/lms/courses')->assertForbidden();
    }

    public function test_screens_load_when_enabled(): void
    {
        $this->actingAs($this->admin);
        $this->enable();
        $this->get('/admin/lms/courses')->assertOk();
    }

    public function test_create_course(): void
    {
        $this->actingAs($this->admin);
        $this->enable();

        $this->post('/admin/lms/courses', [
            'title' => 'Algebra Basics', 'class_id' => $this->class->id, 'subject_id' => $this->subject->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('lms_courses', ['school_id' => $this->school->id, 'title' => 'Algebra Basics']);
    }

    public function test_add_lesson_publish_and_assignment(): void
    {
        $this->actingAs($this->admin);
        $this->enable();
        $course = $this->makeCourse();

        $this->get("/admin/lms/courses/{$course->id}")->assertOk();

        $this->post("/admin/lms/courses/{$course->id}/lessons", [
            'title' => 'Intro', 'content_type' => 'text', 'body_text' => 'Welcome to algebra.',
        ])->assertRedirect();
        $lesson = Lesson::where('course_id', $course->id)->firstOrFail();
        $this->assertFalse((bool) $lesson->is_published);

        $this->patch("/admin/lms/courses/{$course->id}/lessons/{$lesson->id}/publish")->assertRedirect();
        $this->assertTrue((bool) $lesson->fresh()->is_published);

        $this->post("/admin/lms/courses/{$course->id}/assignments", ['title' => 'HW1', 'max_marks' => 20, 'due_date' => '2026-12-01T23:59'])->assertRedirect();
        $this->assertDatabaseHas('lms_assignments', ['course_id' => $course->id, 'title' => 'HW1', 'max_marks' => 20]);
    }

    public function test_grade_submission_and_over_max_rejected(): void
    {
        $this->actingAs($this->admin);
        $this->enable();
        $course = $this->makeCourse();
        $assignment = Assignment::create([
            'school_id' => $this->school->id, 'course_id' => $course->id, 'title' => 'HW1', 'max_marks' => 20,
            'due_date' => now()->addWeek(),
        ]);
        $student = Student::create(['school_id' => $this->school->id, 'name' => 'Pupil', 'gender' => 'male', 'admission_number' => 'ADM-1', 'status' => 'active']);
        $submission = Submission::create([
            'school_id' => $this->school->id, 'assignment_id' => $assignment->id, 'student_id' => $student->id,
            'file_path' => 'lms/x.pdf', 'submitted_at' => now(),
        ]);

        // valid grade
        $this->patch("/admin/lms/submissions/{$submission->id}/grade", ['marks_awarded' => 18])->assertRedirect();
        $this->assertDatabaseHas('lms_submissions', ['id' => $submission->id, 'marks_awarded' => 18]);

        // over max → error, unchanged
        $this->patch("/admin/lms/submissions/{$submission->id}/grade", ['marks_awarded' => 50])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertEquals(18, $submission->fresh()->marks_awarded);
    }
}
