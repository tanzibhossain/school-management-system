<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\FeeItem\Models\FeeCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FeeCategoryController extends Controller
{
    public function index(): View
    {
        $categories = FeeCategory::where('school_id', app('current_school_id'))
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return view('admin.finance.fee-categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:fee_categories,name,NULL,id,school_id,{$schoolId}"],
            'is_active' => ['nullable', 'boolean'],
        ]);

        FeeCategory::create([
            'school_id' => $schoolId,
            'name'      => $data['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Fee category created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $category = FeeCategory::where('school_id', $schoolId)->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:fee_categories,name,{$id},id,school_id,{$schoolId}"],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name'      => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Fee category updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $category = FeeCategory::where('school_id', $schoolId)->withCount('items')->findOrFail($id);

        if ($category->items_count > 0) {
            return back()->with('error', 'Cannot delete a category that still has fee items.');
        }

        $category->delete();

        return back()->with('status', 'Fee category deleted.');
    }
}
