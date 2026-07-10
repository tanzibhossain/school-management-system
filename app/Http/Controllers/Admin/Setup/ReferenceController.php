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
 * (groups, versions, shifts). The route supplies {type} via ->defaults().
 */
class ReferenceController extends Controller
{
    /** @var array<string, array{model: class-string<Model>, table: string, label: string, singular: string}> */
    private const TYPES = [
        'groups'   => ['model' => AcademicGroup::class,   'table' => 'groups',   'label' => 'Groups',   'singular' => 'Group'],
        'versions' => ['model' => AcademicVersion::class, 'table' => 'versions', 'label' => 'Versions', 'singular' => 'Version'],
        'shifts'   => ['model' => AcademicShift::class,   'table' => 'shifts',   'label' => 'Shifts',   'singular' => 'Shift'],
    ];

    public function __construct(private readonly AcademicRepository $academic) {}

    public function index(string $type): View
    {
        $meta = $this->meta($type);
        $items = $meta['model']::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderBy('name')
            ->get();

        return view('admin.setup.reference.index', [
            'type'  => $type,
            'label' => $meta['label'],
            'singular' => $meta['singular'],
            'items' => $items,
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $meta = $this->meta($type);
        $schoolId = app('current_school_id');
        $meta['model']::create($this->validated($request, $meta['table'], $schoolId, null) + ['school_id' => $schoolId]);
        $this->academic->flush();

        return back()->with('status', "{$meta['singular']} created.");
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $meta = $this->meta($type);
        $schoolId = app('current_school_id');
        $item = $meta['model']::where('school_id', $schoolId)->findOrFail($id);
        $item->update($this->validated($request, $meta['table'], $schoolId, $id));
        $this->academic->flush();

        return back()->with('status', "{$meta['singular']} updated.");
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $meta = $this->meta($type);
        $item = $meta['model']::where('school_id', app('current_school_id'))->findOrFail($id);
        $item->update(['is_trash' => true]);
        $this->academic->flush();

        return back()->with('status', "{$meta['singular']} deleted.");
    }

    /**
     * @return array{model: class-string<Model>, table: string, label: string, singular: string}
     */
    private function meta(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

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
