<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Mark\Models\GradeBoundary;
use App\Modules\Mark\Models\MarkSetting;
use App\Modules\Mark\Services\GradeTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MarkSettingController extends Controller
{
    /** Grade templates available (config/grading.php). */
    public const GRADE_TEMPLATES = [
        'bd_national_5' => 'BD National (GPA 5.0)',
        'us_letter_4' => 'US Letter (GPA 4.0)',
        'uk_9_1' => 'UK 9–1',
        'percentage_only' => 'Percentage only',
    ];

    public function __construct(private readonly GradeTemplateService $grades) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $classes = SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get();
        $settings = MarkSetting::where('school_id', $schoolId)->get()->keyBy('class_id');
        $boundaryCounts = GradeBoundary::where('school_id', $schoolId)
            ->selectRaw('class_id, count(*) as c')->groupBy('class_id')->pluck('c', 'class_id');

        return view('admin.academics.mark-settings.index', [
            'classes' => $classes,
            'settings' => $settings,
            'boundaryCounts' => $boundaryCounts,
            'templates' => self::GRADE_TEMPLATES,
        ]);
    }

    public function update(Request $request, int $classId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        SchoolClass::where('school_id', $schoolId)->findOrFail($classId);

        $data = $request->validate([
            'mode' => ['required', 'in:mark,grade'],
            'result_strategy' => ['required', 'in:bd_national,simple_average,weighted_average,percentage_only'],
            'grace_marks_cap' => ['nullable', 'numeric', 'min:0'],
            'show_merit_position' => ['nullable', 'boolean'],
        ]);

        $setting = MarkSetting::forClass($schoolId, $classId);
        $setting->update([
            'mode' => $data['mode'],
            'result_strategy' => $data['result_strategy'],
            'grace_marks_cap' => $data['grace_marks_cap'] ?? 0,
            'show_merit_position' => $request->boolean('show_merit_position'),
        ]);

        return back()->with('status', 'Mark settings saved.');
    }

    public function applyTemplate(Request $request, int $classId): RedirectResponse
    {
        $schoolId = app('current_school_id');
        SchoolClass::where('school_id', $schoolId)->findOrFail($classId);

        $data = $request->validate([
            'template' => ['required', 'in:'.implode(',', array_keys(self::GRADE_TEMPLATES))],
        ]);

        try {
            $count = $this->grades->applyGradeTemplate($schoolId, $classId, $data['template']);
        } catch (ValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Applied {$count} grade boundaries.");
    }
}
