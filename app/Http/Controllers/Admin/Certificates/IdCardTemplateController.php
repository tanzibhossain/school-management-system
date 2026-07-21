<?php

namespace App\Http\Controllers\Admin\Certificates;

use App\Modules\IdCard\Models\IdCardTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class IdCardTemplateController extends Controller
{
    public const FIELDS = ['name', 'identifier', 'class', 'section', 'blood_group', 'photo', 'phone'];

    public function index(): View
    {
        $templates = IdCardTemplate::where('school_id', app('current_school_id'))->orderBy('type')->orderBy('name')->get();

        return view('admin.certificates.id-card-templates.index', ['templates' => $templates, 'fields' => self::FIELDS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save(null, $request);

        return back()->with('status', __('Template added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $template = IdCardTemplate::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->save($template, $request);

        return back()->with('status', __('Template updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        IdCardTemplate::where('school_id', app('current_school_id'))->findOrFail($id)->delete();

        return back()->with('status', __('Template deleted.'));
    }

    private function save(?IdCardTemplate $template, Request $request): void
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'type' => ['required', 'in:student,staff'],
            'name' => ['required', 'string', 'max:100'],
            'layout' => ['required', 'in:horizontal_classic,horizontal_modern,vertical,dual_stripe,minimal'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'font' => ['required', 'in:sans,serif,mono'],
            'visible_fields' => ['nullable', 'array'],
            'visible_fields.*' => ['in:'.implode(',', self::FIELDS)],
        ]);
        $data['visible_fields'] = $request->input('visible_fields', ['name', 'identifier', 'photo']);
        $data['is_default'] = $request->boolean('is_default');
        $data['background_color'] = ($data['background_color'] ?? null) ?: '#ffffff';
        $data['accent_color'] = ($data['accent_color'] ?? null) ?: '#1a56db';

        DB::transaction(function () use ($template, $data, $schoolId): void {
            if ($data['is_default']) {
                IdCardTemplate::where('school_id', $schoolId)->where('type', $data['type'])->update(['is_default' => false]);
            }

            if ($template) {
                $template->update($data);
            } else {
                IdCardTemplate::create($data + ['school_id' => $schoolId]);
            }
        });
    }
}
