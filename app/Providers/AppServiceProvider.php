<?php

namespace App\Providers;

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
    }
}
