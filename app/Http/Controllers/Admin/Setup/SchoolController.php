<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Models\School;
use App\Modules\School\Models\SchoolOpeningHour;
use App\Modules\School\Services\ModuleSettingService;
use App\Modules\School\Services\SchoolService;
use App\Modules\Website\Models\SiteSetting;
use App\Modules\Website\Services\SiteSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(
        private readonly SchoolService $schools,
        private readonly SiteSettingService $siteSettings,
        private readonly ModuleSettingService $modules,
    ) {}

    public function edit(): View
    {
        $schoolId = app('current_school_id');
        $school = School::with(['phones', 'openingHours'])->findOrFail($schoolId);

        return view('admin.setup.school.edit', [
            'school' => $school,
            'settings' => SiteSetting::forSchool($schoolId),
            'timezones' => \DateTimeZone::listIdentifiers(),
            'countries' => config('geo.countries'),
            'currencies' => config('geo.currencies'),
            'languages' => config('geo.languages'),
            'moduleSettings' => $this->modules->allForSchool($schoolId),
            'moduleMeta' => ModuleSetting::META,
            'patterns' => [
                'jan_dec' => 'January – December',
                'apr_mar' => 'April – March',
                'jul_jun' => 'July – June',
                'sep_aug' => 'September – August',
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $school = School::findOrFail(app('current_school_id'));

        // Country/currency codes are stored uppercase — normalise before validating
        // against the geo lists (dropdowns already send uppercase; this covers any
        // manual/lowercase input too).
        $request->merge([
            'currency' => strtoupper((string) $request->input('currency')),
            'country_code' => $request->filled('country_code') ? strtoupper((string) $request->input('country_code')) : null,
        ]);

        $validated = $request->validate([
            // Profile
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            // School codes — three configurable label/value pairs
            'institution_code_label' => ['nullable', 'string', 'max:50'],
            'institution_code' => ['nullable', 'string', 'max:50'],
            'school_code_label' => ['nullable', 'string', 'max:50'],
            'school_code' => ['nullable', 'string', 'max:50'],
            'technical_branch_code_label' => ['nullable', 'string', 'max:50'],
            'technical_branch_code' => ['nullable', 'string', 'max:50'],
            'established' => ['nullable', 'integer', 'min:1800', 'max:'.date('Y')],
            'address' => ['nullable', 'string', 'max:2000'],
            'country_code' => ['nullable', 'string', 'size:2', 'in:'.implode(',', array_keys(config('geo.countries')))],
            'currency' => ['required', 'string', 'size:3', 'in:'.implode(',', array_keys(config('geo.currencies')))],
            'timezone' => ['required', 'string', 'timezone:all'],
            'locale' => ['required', 'string', 'in:'.implode(',', array_keys(config('geo.languages')))],
            'academic_year_pattern' => ['required', 'string', 'in:jan_dec,apr_mar,jul_jun,sep_aug'],
            // Appearance / branding (merged in from the old Appearance page)
            'primary_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'heading_color' => ['nullable', 'string', 'max:20'],
            'topbar_text_color' => ['nullable', 'string', 'max:20'],
            'ticker_position' => ['nullable', 'in:above_nav,below_nav,hidden'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            // Images (uploads)
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
            'og_image' => ['nullable', 'image', 'max:2048'],
        ]);

        // ── School profile ──────────────────────────────────────────────────
        $schoolData = collect($validated)->only([
            'name', 'email',
            'institution_code', 'institution_code_label',
            'school_code', 'school_code_label',
            'technical_branch_code', 'technical_branch_code_label',
            'address', 'country_code', 'currency', 'timezone', 'locale', 'academic_year_pattern',
        ])->all();
        // "established" is entered as a plain year; the column stores a date.
        $schoolData['established'] = filled($validated['established'] ?? null) ? $validated['established'].'-01-01' : null;
        if ($path = $this->storeImage($request, 'logo')) {
            $schoolData['logo'] = $path;
        }
        $this->schools->updateSettings($school, $schoolData);

        // ── Appearance / SEO (SiteSetting) ──────────────────────────────────
        $settingData = collect($validated)->only([
            'primary_color', 'accent_color', 'heading_color',
            'topbar_text_color', 'ticker_position', 'meta_title', 'meta_description',
        ])->all();
        if ($path = $this->storeImage($request, 'favicon')) {
            $settingData['favicon'] = $path;
        }
        if ($path = $this->storeImage($request, 'og_image')) {
            $settingData['og_image'] = $path;
        }
        $this->siteSettings->update($school->id, $settingData);

        // ── Phones (dynamic list; each can be flagged to show in the header) ─
        $phones = collect($request->input('phones', []))
            ->filter(fn ($p) => filled($p['phone'] ?? null))
            ->map(fn ($p, $i) => [
                'phone' => $p['phone'],
                'is_primary' => (int) $request->input('primary_phone', 0) === (int) $i,
                'show_in_header' => (bool) ($p['show_in_header'] ?? false),
            ])->values()->all();

        $this->schools->syncPhones($school->id, $phones);

        return back()->with('status', __('School settings saved.'));
    }

    /** Store an uploaded image on the public disk; returns the path or null if none uploaded. */
    private function storeImage(Request $request, string $field): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        return $request->file($field)->store('site', 'public');
    }

    /**
     * Weekly opening hours — drives Attendance working-days (is_open per day).
     */
    public function updateHours(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $request->validate([
            'days' => ['required', 'array'],
            'days.*.open_time' => ['nullable', 'date_format:H:i'],
            'days.*.close_time' => ['nullable', 'date_format:H:i', 'after:days.*.open_time'],
        ]);

        foreach ((array) $request->input('days', []) as $dow => $row) {
            SchoolOpeningHour::updateOrCreate(
                ['school_id' => $schoolId, 'day_of_week' => (int) $dow],
                [
                    'is_open' => (bool) ($row['is_open'] ?? false),
                    'open_time' => $row['open_time'] ?? null,
                    'close_time' => $row['close_time'] ?? null,
                ],
            ); // SchoolOpeningHourObserver flushes the school cache
        }

        return back()->with('status', __('Opening hours saved.'));
    }
}
