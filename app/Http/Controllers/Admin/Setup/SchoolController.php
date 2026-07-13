<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\School\Models\School;
use App\Modules\School\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(private readonly SchoolService $schools) {}

    public function edit(): View
    {
        $school = School::with(['phones', 'openingHours'])->findOrFail(app('current_school_id'));

        return view('admin.setup.school.edit', [
            'school'     => $school,
            'timezones'  => \DateTimeZone::listIdentifiers(),
            'countries'  => config('geo.countries'),
            'currencies' => config('geo.currencies'),
            'languages'  => config('geo.languages'),
            'patterns'   => [
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
            'currency'     => strtoupper((string) $request->input('currency')),
            'country_code' => $request->filled('country_code') ? strtoupper((string) $request->input('country_code')) : null,
        ]);

        $data = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'email'                  => ['nullable', 'email'],
            'institution_code'       => ['nullable', 'string', 'max:50'],
            'institution_code_label' => ['nullable', 'string', 'max:50'],
            'established'            => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'address'               => ['nullable', 'string', 'max:2000'],
            'country_code'          => ['nullable', 'string', 'size:2', 'in:' . implode(',', array_keys(config('geo.countries')))],
            'currency'              => ['required', 'string', 'size:3', 'in:' . implode(',', array_keys(config('geo.currencies')))],
            'timezone'              => ['required', 'string', 'timezone:all'],
            'locale'                => ['required', 'string', 'in:' . implode(',', array_keys(config('geo.languages')))],
            'academic_year_pattern' => ['required', 'string', 'in:jan_dec,apr_mar,jul_jun,sep_aug'],
        ]);

        // "established" is entered as a plain year; the column stores a date.
        $data['established'] = filled($data['established'] ?? null) ? $data['established'] . '-01-01' : null;

        $this->schools->updateSettings($school, $data);

        // Phones (optional dynamic list)
        $phones = collect($request->input('phones', []))
            ->filter(fn ($p) => filled($p['phone'] ?? null))
            ->map(fn ($p, $i) => [
                'phone'      => $p['phone'],
                'label'      => $p['label'] ?? null,
                'is_primary' => (int) $request->input('primary_phone', 0) === (int) $i,
            ])->values()->all();

        $this->schools->syncPhones($school->id, $phones);

        return back()->with('status', 'School settings saved.');
    }

    /**
     * Weekly opening hours — drives Attendance working-days (is_open per day).
     */
    public function updateHours(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $request->validate([
            'days'              => ['required', 'array'],
            'days.*.open_time'  => ['nullable', 'date_format:H:i'],
            'days.*.close_time' => ['nullable', 'date_format:H:i', 'after:days.*.open_time'],
        ]);

        foreach ((array) $request->input('days', []) as $dow => $row) {
            \App\Modules\School\Models\SchoolOpeningHour::updateOrCreate(
                ['school_id' => $schoolId, 'day_of_week' => (int) $dow],
                [
                    'is_open'    => (bool) ($row['is_open'] ?? false),
                    'open_time'  => $row['open_time'] ?? null,
                    'close_time' => $row['close_time'] ?? null,
                ],
            ); // SchoolOpeningHourObserver flushes the school cache
        }

        return back()->with('status', 'Opening hours saved.');
    }
}
