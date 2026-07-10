<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Services\ModuleSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ModuleController extends Controller
{
    /** Friendly labels + descriptions for the optional modules. */
    private const META = [
        'payroll'   => ['Payroll', 'Salary components, payroll runs, salary certificates.'],
        'lms'       => ['LMS', 'Courses, lessons, assignments, AI submission checks.'],
        'library'   => ['Library', 'Books, members, borrow/return workflow.'],
        'transport' => ['Transport', 'Routes, vehicles, drivers, student assignments.'],
        'messaging' => ['Messaging', 'In-app threaded messaging between staff and families.'],
    ];

    public function __construct(private readonly ModuleSettingService $modules) {}

    public function index(): View
    {
        $settings = $this->modules->allForSchool(app('current_school_id'));

        return view('admin.setup.modules.index', [
            'settings' => $settings,
            'meta'     => self::META,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $enabled = (array) $request->input('enabled', []);

        foreach (ModuleSetting::MODULES as $module) {
            $this->modules->setEnabled($schoolId, $module, in_array($module, $enabled, true));
        }

        return back()->with('status', 'Module settings saved.');
    }
}
