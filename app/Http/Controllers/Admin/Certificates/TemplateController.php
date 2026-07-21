<?php

namespace App\Http\Controllers\Admin\Certificates;

use App\Modules\Certificate\Models\TestimonialTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = TestimonialTemplate::where('school_id', app('current_school_id'))->orderBy('name')->get();

        return view('admin.certificates.templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save(null, $request);

        return back()->with('status', 'Template added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $template = TestimonialTemplate::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->save($template, $request);

        return back()->with('status', 'Template updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        TestimonialTemplate::where('school_id', app('current_school_id'))->findOrFail($id)->delete();

        return back()->with('status', 'Template deleted.');
    }

    private function save(?TestimonialTemplate $template, Request $request): void
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'template_body' => ['required', 'string'],
            'footer_text' => ['nullable', 'string', 'max:500'],
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'signatory_designation' => ['nullable', 'string', 'max:120'],
        ]);
        $data['is_default'] = $request->boolean('is_default');

        DB::transaction(function () use ($template, $data, $schoolId): void {
            if ($data['is_default']) {
                TestimonialTemplate::where('school_id', $schoolId)->update(['is_default' => false]);
            }

            if ($template) {
                $template->update($data);
            } else {
                TestimonialTemplate::create($data + ['school_id' => $schoolId]);
            }
        });
    }
}
