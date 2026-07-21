<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\FeeItem\Models\FeeDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FeeDiscountController extends Controller
{
    public function index(): View
    {
        $discounts = FeeDiscount::where('school_id', app('current_school_id'))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.finance.fee-discounts.index', compact('discounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        FeeDiscount::create($this->validated($request) + ['school_id' => app('current_school_id')]);

        return back()->with('status', __('Discount created.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $discount = FeeDiscount::where('school_id', app('current_school_id'))->findOrFail($id);
        $discount->update($this->validated($request));

        return back()->with('status', __('Discount updated.'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        $discount = FeeDiscount::where('school_id', app('current_school_id'))->findOrFail($id);
        $discount->update(['is_active' => false]);

        return back()->with('status', __('Discount deactivated.'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0', $request->input('type') === 'percentage' ? 'max:100' : 'max:99999999'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
