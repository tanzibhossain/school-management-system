<?php

namespace App\Http\Controllers\Admin\Platform;

use App\Modules\Platform\Models\Plan;
use App\Modules\Platform\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Super-Admin portal (Blade) — plan catalogue CRUD. Thin over PlanService. */
class PlanController extends Controller
{
    public function __construct(private readonly PlanService $service) {}

    public function index(): View
    {
        return view('platform.plans.index', ['plans' => Plan::orderBy('sort_order')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->service->create($this->validated($request));

        return back()->with('status', 'Plan created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $plan = Plan::findOrFail($id);
        $this->service->update($plan, $this->validated($request, $plan->id));

        return back()->with('status', 'Plan updated.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $slug = Rule::unique('plans', 'slug');
        if ($ignoreId !== null) {
            $slug->ignore($ignoreId);
        }

        return $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'slug'          => ['required', 'string', 'max:100', $slug],
            'price_monthly' => ['nullable', 'numeric', 'min:0'],
            'price_yearly'  => ['nullable', 'numeric', 'min:0'],
            'currency'      => ['nullable', 'string', 'size:3'],
            'max_students'  => ['nullable', 'integer', 'min:1'],
            'max_staff'     => ['nullable', 'integer', 'min:1'],
            'trial_days'    => ['nullable', 'integer', 'min:1'],
            'is_self_serve' => ['boolean'],
            'is_active'     => ['boolean'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
