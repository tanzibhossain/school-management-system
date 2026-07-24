<?php

namespace App\Http\Controllers\Admin\Website;

use App\Modules\Website\Models\PageTemplate;
use App\Modules\Website\Services\PageTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Management screen for saved PageTemplates (Website > Page Templates) —
 * PageTemplateService::saveAsTemplate() (the page editor's "Save as
 * Template" action, see docs/modules/28-elementor-block-editor-plan.md §7j)
 * had no way to rename or delete a template once created; this closes that
 * gap. Deliberately scoped to THIS school's own templates only — global
 * starter templates (school_id null, seeded, shared across every school)
 * are read-only here, never editable/deletable from a single school's admin.
 */
class PageTemplateController extends Controller
{
    public function __construct(private readonly PageTemplateService $templates) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.website.page-templates.index', [
            'templates' => PageTemplate::where('school_id', $schoolId)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $template = PageTemplate::where('school_id', $schoolId)->findOrFail($id);
        $data = $request->validate(['name' => ['required', 'string', 'max:150']]);

        $this->templates->rename($template, $data['name']);

        return redirect()->route('admin.page-templates.index')->with('status', __('Template renamed.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $template = PageTemplate::where('school_id', $schoolId)->findOrFail($id);
        $this->templates->delete($template);

        return redirect()->route('admin.page-templates.index')->with('status', __('Template deleted.'));
    }
}
