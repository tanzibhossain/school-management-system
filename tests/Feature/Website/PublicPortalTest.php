<?php

namespace Tests\Feature\Website;

use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Examination\Models\Exam;
use App\Modules\Examination\Models\ExamType;
use App\Modules\Mark\Models\ExamResult;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentAcademic;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Models\PageLayout;
use App\Modules\Website\Models\PageRedirect;

class PublicPortalTest extends WebsiteTestCase
{
    public function test_public_can_fetch_a_published_page_by_slug(): void
    {
        $page = Page::create([
            'school_id' => $this->school->id,
            'slug' => 'about-us',
            'title' => 'About Us',
            'status' => 'published',
        ]);
        PageLayout::create([
            'school_id' => $this->school->id,
            'page_id' => $page->id,
            'layout_json' => ['sections' => ['hero']],
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->getJson('/api/public/pages/about-us')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'about-us', 'title' => 'About Us']);
    }

    public function test_unpublished_page_is_not_found(): void
    {
        Page::create([
            'school_id' => $this->school->id,
            'slug' => 'draft-page',
            'title' => 'Draft',
            'status' => 'draft',
        ]);

        $this->getJson('/api/public/pages/draft-page')->assertNotFound();
    }

    public function test_public_can_fetch_site_chrome(): void
    {
        // PUT inserts a new versioned SiteLayout row every time, so this correctly
        // returns 201, not 200 — see the equivalent note in SiteLayoutTest.
        $this->withToken($this->adminToken())
            ->putJson('/api/v2/website/site-layouts/header', ['layout_json' => ['logo' => 'x']])
            ->assertCreated();
        $this->withToken($this->adminToken())
            ->postJson('/api/v2/website/site-layouts/header/publish')
            ->assertOk();

        $this->getJson('/api/public/site-chrome')
            ->assertOk()
            ->assertJsonStructure(['data' => ['header', 'footer', 'settings']]);
    }

    public function test_public_can_resolve_a_multi_hop_redirect(): void
    {
        PageRedirect::create(['school_id' => $this->school->id, 'old_slug' => 'old-1', 'new_slug' => 'old-2']);
        PageRedirect::create(['school_id' => $this->school->id, 'old_slug' => 'old-2', 'new_slug' => 'final-slug']);

        $this->getJson('/api/public/redirect/old-1')
            ->assertOk()
            ->assertJsonFragment(['destination_slug' => 'final-slug']);
    }

    public function test_redirect_not_found_returns_404(): void
    {
        $this->getJson('/api/public/redirect/nowhere')->assertNotFound();
    }

    public function test_public_notices_returns_visible_announcements_only(): void
    {
        Announcement::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'title' => 'Visible Notice',
            'body' => 'Body text',
            'audience' => 'all',
            'publish_at' => now()->subDay(),
            'is_pinned' => false,
            'is_trash' => false,
        ]);
        Announcement::create([
            'school_id' => $this->school->id,
            'created_by' => $this->admin->id,
            'title' => 'Future Notice',
            'body' => 'Not yet',
            'audience' => 'all',
            'publish_at' => now()->addDay(),
            'is_pinned' => false,
            'is_trash' => false,
        ]);

        $this->getJson('/api/public/notices')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Visible Notice']);
    }

    public function test_public_staff_list_filters_by_designation(): void
    {
        $teacher = Designation::create(['school_id' => $this->school->id, 'name' => 'Teacher']);
        $accountant = Designation::create(['school_id' => $this->school->id, 'name' => 'Accountant']);

        Staff::create([
            'school_id' => $this->school->id,
            'designation_id' => $teacher->id,
            'name' => 'Jane Teacher',
            'gender' => 'female',
            'status' => 'active',
        ]);
        Staff::create([
            'school_id' => $this->school->id,
            'designation_id' => $accountant->id,
            'name' => 'Sam Accountant',
            'gender' => 'male',
            'status' => 'active',
        ]);

        $this->getJson('/api/public/staff?designation_id='.$teacher->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Jane Teacher', 'designation' => 'Teacher']);
    }

    public function test_public_class_routine_returns_entries_for_class_and_section(): void
    {
        $period = \App\Modules\Academic\Models\RoutinePeriod::create([
            'school_id' => $this->school->id,
            'name' => 'Period 1',
            'start_time' => '09:00',
            'end_time' => '09:45',
        ]);
        $room = \App\Modules\Academic\Models\RoutineRoom::create([
            'school_id' => $this->school->id,
            'name' => 'Room 101',
        ]);
        $subject = \App\Modules\Academic\Models\Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
        ]);

        ClassRoutine::create([
            'school_id' => $this->school->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'period_id' => $period->id,
            'day_of_week' => 'monday',
        ]);

        $this->getJson("/api/public/routine/{$this->class->id}?section_id={$this->section->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['day_of_week' => 'monday', 'subject' => 'Mathematics']);
    }

    public function test_public_stats_returns_active_counts(): void
    {
        Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-1',
            'name' => 'Student One',
            'gender' => 'male',
            'status' => 'active',
        ]);
        Staff::create([
            'school_id' => $this->school->id,
            'name' => 'Staff One',
            'gender' => 'female',
            'status' => 'active',
        ]);

        $this->getJson('/api/public/stats')
            ->assertOk()
            ->assertJsonFragment(['active_students' => 1, 'active_staff' => 1]);
    }

    public function test_public_can_check_a_locked_result_by_roll_number(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-2',
            'name' => 'Result Student',
            'gender' => 'male',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_number' => '10',
            'is_current' => true,
        ]);

        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Final Term']);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'exam_type_id' => $examType->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'title' => 'Final Term 2026',
            'start_date' => now()->subWeek(),
            'end_date' => now()->subDays(3),
            'status' => 'published',
        ]);

        ExamResult::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'total_marks' => 450,
            'total_possible' => 500,
            'percentage' => 90,
            'grade' => 'A+',
            'gpa' => 5.0,
            'is_pass' => true,
            'merit_position' => 1,
            'is_locked' => true,
        ]);

        $this->postJson('/api/public/results/check', ['exam_id' => $exam->id, 'roll_number' => '10'])
            ->assertOk()
            ->assertJsonFragment(['grade' => 'A+', 'is_pass' => true, 'merit_position' => 1]);
    }

    public function test_result_check_returns_404_when_result_is_not_locked(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'admission_number' => 'ADM-3',
            'name' => 'Unlocked Student',
            'gender' => 'male',
            'status' => 'active',
        ]);
        StudentAcademic::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'section_id' => $this->section->id,
            'roll_number' => '11',
            'is_current' => true,
        ]);

        $examType = ExamType::create(['school_id' => $this->school->id, 'name' => 'Mid Term']);
        $exam = Exam::create([
            'school_id' => $this->school->id,
            'exam_type_id' => $examType->id,
            'academic_year_id' => $this->year->id,
            'class_id' => $this->class->id,
            'title' => 'Mid Term 2026',
            'start_date' => now()->subWeek(),
            'end_date' => now()->subDays(3),
            'status' => 'published',
        ]);

        ExamResult::create([
            'school_id' => $this->school->id,
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'is_locked' => false,
        ]);

        $this->postJson('/api/public/results/check', ['exam_id' => $exam->id, 'roll_number' => '11'])
            ->assertNotFound();
    }
}
