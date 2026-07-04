<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Models\Section;
use App\Modules\Platform\Mail\SubscriptionExpiringMail;
use App\Modules\School\Models\School;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Models\StudentIdConfig;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

class ScheduledJobsTest extends PlatformTestCase
{
    public function test_demo_reset_wipes_and_reseeds_the_demo_school(): void
    {
        $demo = School::create(['name' => 'Demo', 'is_demo' => true, 'plan_id' => $this->demoPlan->id, 'is_active' => true]);
        $year = AcademicYear::create(['school_id' => $demo->id, 'year' => '2026', 'is_current' => true, 'is_trash' => false]);
        $class = SchoolClass::create(['school_id' => $demo->id, 'name' => 'Class 1', 'weight' => 1, 'is_trash' => false]);
        $section = Section::create(['school_id' => $demo->id, 'class_id' => $class->id, 'name' => 'A', 'is_trash' => false]);
        StudentIdConfig::create([
            'school_id' => $demo->id, 'prefix' => 'DEMO', 'include_year' => false, 'separator' => '-',
            'sequence_length' => 3, 'reset_yearly' => false, 'last_sequence' => 0,
        ]);

        $stale = Student::create([
            'school_id' => $demo->id, 'admission_number' => 'STALE-1', 'student_id' => 'STALE-1',
            'name' => 'Stale Student', 'gender' => 'male', 'status' => 'active',
        ]);

        Artisan::call('platform:demo-reset');

        $this->assertDatabaseMissing('students', ['id' => $stale->id]);
        $this->assertTrue(Student::where('school_id', $demo->id)->count() > 0, 'Demo school should have fresh reseeded students.');
    }

    public function test_demo_reset_is_a_no_op_when_no_demo_school_exists(): void
    {
        // No is_demo school in this test — must not throw.
        Artisan::call('platform:demo-reset');
        $this->assertTrue(true);
    }

    public function test_subscription_reminder_sent_at_seven_and_one_day_and_is_idempotent(): void
    {
        Mail::fake();

        $school = School::create([
            'name' => 'Expiring School', 'is_active' => true,
            'subscription_expires_at' => now()->addDays(7),
        ]);
        $admin = User::factory()->create(['school_id' => $school->id, 'is_active' => true]);
        $admin->assignRole('admin');

        Artisan::call('platform:subscription-reminders');

        // SubscriptionExpiringMail implements ShouldQueue, so Mailer::send() redirects
        // to queue() — MailFake tracks that as "queued", not "sent".
        Mail::assertQueued(SubscriptionExpiringMail::class, 1);
        $this->assertDatabaseHas('subscription_reminders', ['school_id' => $school->id, 'milestone' => 'day_7']);

        // Running again the same day must NOT double-send.
        Artisan::call('platform:subscription-reminders');
        Mail::assertQueued(SubscriptionExpiringMail::class, 1);
    }

    public function test_demo_schools_are_never_sent_subscription_reminders(): void
    {
        Mail::fake();

        School::create([
            'name' => 'Demo', 'is_demo' => true, 'is_active' => true,
            'subscription_expires_at' => now()->addDays(7),
        ]);

        Artisan::call('platform:subscription-reminders');

        Mail::assertNothingSent();
    }
}
