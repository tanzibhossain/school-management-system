<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Models\SchoolClass;
use App\Modules\FeeItem\Models\FeeCategory;
use App\Modules\FeeItem\Models\FeeItem;
use App\Modules\FeeItem\Services\FeeItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class FeeItemController extends Controller
{
    public function __construct(private readonly FeeItemService $feeItems) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        $items = FeeItem::where('school_id', $schoolId)
            ->with(['category:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('admin.finance.fee-items.index', [
            'items' => $items,
            'categories' => FeeCategory::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'years' => AcademicYear::where('school_id', $schoolId)->where('is_trash', false)->orderByDesc('year')->get(['id', 'year']),
            'classes' => SchoolClass::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->feeItems->make(app('current_school_id'), $this->validated($request, app('current_school_id')));

        return back()->with('status', 'Fee item created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $item = FeeItem::where('school_id', $schoolId)->findOrFail($id);
        $this->feeItems->modify($item, $this->validated($request, $schoolId));

        return back()->with('status', 'Fee item updated.');
    }

    public function deactivate(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $item = FeeItem::where('school_id', $schoolId)->findOrFail($id);
        $this->feeItems->deactivate($item);

        return back()->with('status', 'Fee item deactivated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $schoolId): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', "exists:fee_categories,id,school_id,{$schoolId}"],
            'academic_year_id' => ['required', 'integer', "exists:academic_years,id,school_id,{$schoolId}"],
            'class_id' => ['nullable', 'integer', "exists:classes,id,school_id,{$schoolId}"],
            'name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'frequency' => ['required', 'in:monthly,quarterly,yearly,one_time'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'is_mandatory' => ['nullable', 'boolean'],
        ], [], ['category_id' => 'category', 'academic_year_id' => 'academic year', 'class_id' => 'class']);

        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['is_active'] = true;

        return $data;
    }
}
