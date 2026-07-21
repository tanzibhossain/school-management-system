<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\SchoolClass;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function __construct(private readonly AcademicRepository $academic) {}

    public function index(): View
    {
        $classes = SchoolClass::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->withCount(['sections' => fn ($q) => $q->where('is_trash', false)])
            ->orderBy('name')
            ->get();

        return view('admin.setup.classes.index', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:classes,name,NULL,id,school_id,{$schoolId},is_trash,0"],
            'min_age' => ['nullable', 'integer', 'min:1', 'max:100'],
            'max_age' => ['nullable', 'integer', 'min:1', 'max:100', 'gte:min_age'],
        ]);

        SchoolClass::create($data + ['school_id' => $schoolId]);
        $this->academic->flush();

        return back()->with('status', __('Class Created.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $class = SchoolClass::where('school_id', $schoolId)->findOrFail($id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', "unique:classes,name,{$id},id,school_id,{$schoolId},is_trash,0"],
            'min_age' => ['nullable', 'integer', 'min:1', 'max:100'],
            'max_age' => ['nullable', 'integer', 'min:1', 'max:100', 'gte:min_age'],
        ]);

        $class->update($data);
        $this->academic->flush();

        return back()->with('status', __('Class Updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $class = SchoolClass::where('school_id', $schoolId)
            ->withCount(['sections' => fn ($q) => $q->where('is_trash', false)])
            ->findOrFail($id);

        if ($class->sections_count > 0) {
            return back()->with('error', 'Remove this class\'s sections before deleting it.');
        }

        $class->update(['is_trash' => true]);
        $this->academic->flush();

        return back()->with('status', __('Class Deleted.'));
    }
}
