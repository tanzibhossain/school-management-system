<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\Academic\Models\Subject;
use App\Modules\Language\Jobs\SuggestStaffTranslationJob;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Services\TranslationService;
use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use App\Modules\Staff\Models\Staff;
use App\Modules\Staff\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffController extends Controller
{
    private const TRANSLATABLE_FIELDS = ['name'];

    public function __construct(
        private readonly StaffService $staff,
        private readonly TranslationService $translations,
    ) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $staff = Staff::where('school_id', $schoolId)
            ->where('is_trash', false)
            ->with(['designation:id,name', 'department:id,name', 'subject:id,name'])
            ->orderBy('name')
            ->get();

        return view('admin.people.staff.index', [
            'staff' => $staff,
            'designations' => Designation::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
            'subjects' => Subject::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
            // docs/modules/30-multilingual-content-plan.md Phase 4/5 — same
            // "active languages minus the default" list School's own editor
            // uses for its translation panels.
            'contentLanguages' => Language::activeCached()->reject(fn (Language $l) => $l->code === Language::defaultCode())->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, app('current_school_id'));

        $this->staff->hire(app('current_school_id'), Arr::except($data, 'translations'));

        return back()->with('status', __('Staff Member Hired.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($id);
        $data = $this->validated($request, $schoolId);
        $staff->update(Arr::except($data, 'translations'));

        $this->translations->saveMany($staff, $this->translationsPayload($data));

        return back()->with('status', __('Staff Member Updated.'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($id);
        $this->staff->terminate($staff);

        return back()->with('status', __('Staff Member Deactivated.'));
    }

    /**
     * "Suggest translation (AI)" — docs/modules/30-multilingual-content-plan.md
     * Phase 5. Fills only the currently-empty name translation for one
     * locale; never overwrites a field the admin (or a previous run)
     * already translated.
     *
     * Staff is edited in a modal (not a dedicated page), so a plain form
     * POST+redirect here would close the modal — see
     * AnnouncementController::suggestTranslation()'s own comment for the
     * full reasoning. The button's JS fetch()es this with
     * X-Requested-With (ajax()) and fills the field in place from the JSON
     * response; a real form submit still falls back to the old redirect.
     */
    public function suggestTranslation(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $schoolId = app('current_school_id');
        $staff = Staff::where('school_id', $schoolId)->findOrFail($id);
        $locale = Language::resolve($request->input('locale'));

        if ($locale === Language::defaultCode()) {
            $message = __('Nothing to translate — that is the default language.');

            return $request->ajax() ? response()->json(['message' => $message], 422) : back()->with('status', $message);
        }

        // dispatchSync() — see SchoolController::suggestTranslation()'s own
        // comment: queued dispatch() returns before Horizon actually runs
        // the job under this app's normal QUEUE_CONNECTION=redis.
        SuggestStaffTranslationJob::dispatchSync($staff->id, $locale);

        if ($request->ajax()) {
            return response()->json(['fields' => ['name' => $staff->trans('name', $locale)]]);
        }

        return back()->with('status', __('Translation suggestions filled in below — review before saving.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $schoolId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'designation_id' => ['nullable', 'integer', "exists:designations,id,school_id,{$schoolId}"],
            'department_id' => ['nullable', 'integer', "exists:departments,id,school_id,{$schoolId}"],
            'subject_id' => ['nullable', 'integer', "exists:subjects,id,school_id,{$schoolId}"],
            'gender' => ['nullable', 'in:male,female,other'],
            'dob' => ['nullable', 'date'],
            'joining_date' => ['nullable', 'date'],
            // NOT NULL enum column: may be omitted (DB default 'permanent') but never null.
            'employment_type' => ['sometimes', 'required', Rule::in(Staff::EMPLOYMENT_TYPES)],
            'basic_salary' => ['nullable', 'numeric', 'min:0'],
            'rfid_number' => ['nullable', 'string', 'max:50'],
            'translations' => ['nullable', 'array'],
            'translations.*' => ['array'],
            'translations.*.*' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'designation_id' => 'designation',
            'department_id' => 'department',
            'basic_salary' => 'basic salary',
        ]);
    }

    /**
     * Whitelists the submitted translations payload against the currently
     * active, non-default locales and against TRANSLATABLE_FIELDS — never
     * trusts an arbitrary locale/field key straight from the request, same
     * defensive pattern as SchoolController::update().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<string, mixed>>
     */
    private function translationsPayload(array $data): array
    {
        $activeLocales = Language::activeCached()->pluck('code')->reject(fn ($code) => $code === Language::defaultCode());
        $submitted = (array) ($data['translations'] ?? []);
        $out = [];

        foreach ($activeLocales as $locale) {
            $fields = is_array($submitted[$locale] ?? null) ? $submitted[$locale] : [];
            $out[$locale] = array_intersect_key($fields, array_flip(self::TRANSLATABLE_FIELDS));
        }

        return $out;
    }
}
