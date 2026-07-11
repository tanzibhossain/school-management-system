<?php

namespace App\Http\Controllers\Admin\Modules\Payroll;

use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\Payroll\Services\SalaryComponentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SalaryComponentController extends Controller
{
    public function __construct(private readonly SalaryComponentService $components) {}

    public function index(): View
    {
        $components = SalaryComponent::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderBy('component_type')
            ->orderBy('sort_order')
            ->get();

        return view('admin.modules.payroll.components.index', compact('components'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->components->create(app('current_school_id'), $this->validated($request));

        return back()->with('status', 'Component added.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $component = SalaryComponent::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->components->update($component, $this->validated($request));

        return back()->with('status', 'Component updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $component = SalaryComponent::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->components->trash($component);

        return back()->with('status', 'Component removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:100'],
            'component_type' => ['required', 'in:earning,deduction'],
            'sort_order'     => ['nullable', 'integer', 'min:0', 'max:255'],
        ], [], ['component_type' => 'type']);
        $data['is_default'] = $request->boolean('is_default');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
