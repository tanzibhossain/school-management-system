<?php

namespace App\Providers;

use App\Models\User;
use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\ClassRoutine;
use App\Modules\Academic\Observers\AcademicYearObserver;
use App\Modules\Academic\Observers\ClassRoutineObserver;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Models\SchoolPhone;
use App\Modules\School\Observers\SchoolObserver;
use App\Modules\School\Observers\SchoolOpeningHourObserver;
use App\Modules\School\Observers\SchoolPhoneObserver;
use App\Modules\Announcement\Models\Announcement;
use App\Modules\Announcement\Observers\AnnouncementObserver;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\FeeItem\Observers\FeeItemObserver;
use App\Modules\Payment\Models\Invoice;
use App\Modules\Payment\Observers\InvoiceObserver;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Observers\StaffObserver;
use App\Modules\Student\Models\Student;
use App\Modules\Student\Observers\StudentObserver;
use App\Modules\User\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── School module observers ───────────────────────────────────────────
        School::observe(SchoolObserver::class);
        SchoolPhone::observe(SchoolPhoneObserver::class);
        SchoolOpeningHour::observe(SchoolOpeningHourObserver::class);

        // ── Academic module observers ─────────────────────────────────────────
        AcademicYear::observe(AcademicYearObserver::class);
        ClassRoutine::observe(ClassRoutineObserver::class);

        // ── User module observers ─────────────────────────────────────────────
        User::observe(UserObserver::class);

        // ── Announcement module observers ─────────────────────────────────────
        Announcement::observe(AnnouncementObserver::class);

        // ── FeeItem module observers ──────────────────────────────────────────
        FeeItem::observe(FeeItemObserver::class);

        // ── Payment module observers ──────────────────────────────────────────
        Invoice::observe(InvoiceObserver::class);

        // ── Staff module observers ────────────────────────────────────────────
        Staff::observe(StaffObserver::class);

        // ── Student module observers ──────────────────────────────────────────
        Student::observe(StudentObserver::class);
    }
}
