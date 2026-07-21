<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\AcademicGroup;
use App\Modules\Academic\Models\AcademicShift;
use App\Modules\Academic\Models\AcademicVersion;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

/**
 * One controller for the three name-only academic reference lists
 * (groups, versions, shifts). The {type} comes from the route's ->defaults(),
 * read via $request->route()->parameter() — NOT a method arg: a defaulted param
 * mixed with a URL {id} binds positionally and would swap the two.
 */
class ReferenceController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, table: string, label: string, singular: string}> */
    private const TYPES = [
        'groups' => ['model' => AcademicGroup::class,   'table' => 'groups',   'label' => 'Groups',   'singular' => 'Group'],
        'versions' => ['model' => AcademicVersion::class, 'table' => 'versions', 'label' => 'Versions', 'singular' => 'Version'],
        'shifts' => ['model' => AcademicShift::class,   'table' => 'shifts',   'label' => 'Shifts',   'singular' => 'Shift'],
    ];

    public function __construct(private readonly AcademicRepository $academic) {}

    public function index(Request $request): View
    {
        $meta = $this->meta($request);
        $items = $meta['model']::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderBy('name')
            ->get();

        return view('admin.setup.reference.index', [
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
        $this->academic->flush();

        return back()->with('status', "{$meta['singular']} created.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $meta = $this->meta($request);
        $schoolId = app('current_school_id');
        $item = $meta['model']::where('school_id', $schoolId)->findOrFail($id);
        $item->update($this->validated($request, $meta['table'], $schoolId, $id));
        $this->academic->flush();

        return back()->with('status', "{$meta['singular']} updated.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $meta = $this->meta($request);
        $item = $meta['model']::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);
        $this->academic->flush();

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
            'name' => ['required', 'string', 'max:100', "unique:{$table},name,{$ignore},id,school_id,{$schoolId},is_trash,0"],
        ]);
    }
}
