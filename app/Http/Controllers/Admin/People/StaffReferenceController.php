<?php

namespace App\Http\Controllers\Admin\People;

use App\Modules\Staff\Models\Department;
use App\Modules\Staff\Models\Designation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * Designations and Departments — simple per-school name lists on the Staff
 * module. Both are `['school_id', 'name']` with a `staff()` relation, so one
 * controller serves both. The {type} comes from the route's ->defaults(), read
 * via $request->route()->parameter() (not a method arg — mixing a defaulted
 * param with a URL {id} binds positionally and swaps the two).
 */
class StaffReferenceController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, table: string, label: string, singular: string}> */
    private const TYPES = [
        'designations' => ['model' => Designation::class, 'table' => 'designations', 'label' => 'Designations', 'singular' => 'Designation'],
        'departments' => ['model' => Department::class,  'table' => 'departments',  'label' => 'Departments',  'singular' => 'Department'],
    ];

    public function index(Request $request): View
    {
        $meta = $this->meta($request);
        $items = $meta['model']::where('school_id', app('current_school_id'))
            ->withCount('staff')
            ->orderBy('name')
            ->get();

        return view('admin.people.reference.index', [
            'type' => $request->route()->parameter('type'),
            'label' => $meta['label'],
            'singular' => $meta['singular'],
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $meta = $this->meta($request);
        $schoolId = app('current_school_id');
        $meta['model']::create($this->validated($request, $meta['table'], $schoolId, null) + ['school_id' => $schoolId]);

        return back()->with('status', "{$meta['singular']} created.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $meta = $this->meta($request);
        $schoolId = app('current_school_id');
        $item = $meta['model']::where('school_id', $schoolId)->findOrFail($id);
        $item->update($this->validated($request, $meta['table'], $schoolId, $id));

        return back()->with('status', "{$meta['singular']} updated.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $meta = $this->meta($request);
        $item = $meta['model']::where('school_id', app('current_school_id'))->withCount('staff')->findOrFail($id);

        if ($item->staff_count > 0) {
            return back()->with('error', "Cannot delete a {$meta['singular']} that still has staff assigned.");
        }

        $item->delete();

        return back()->with('status', "{$meta['singular']} deleted.");
    }

    /**
     * @return array{model: class-string<Model>, table: string, label: string, singular: string}
     */
    private function meta(Request $request): array
    {
        $type = $request->route()->parameter('type');
        abort_unless(is_string($type) && isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $table, int $schoolId, ?int $id): array
    {
        $ignore = $id ?? 'NULL';

        return $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:{$table},name,{$ignore},id,school_id,{$schoolId}"],
        ]);
    }
}
