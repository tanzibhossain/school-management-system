<?php

namespace App\Http\Controllers\Admin;

use App\Modules\Academic\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ClassController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderBy('name')
            ->get();

        return view('admin.classes.index', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        SchoolClass::create($data + ['school_id' => app('current_school_id')]);

        return back()->with('status', 'Class created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $class = SchoolClass::where('school_id', app('current_school_id'))->findOrFail($id);
        $class->update($request->validate(['name' => ['required', 'string', 'max:100']]));

        return back()->with('status', 'Class updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $class = SchoolClass::where('school_id', app('current_school_id'))->findOrFail($id);
        $class->update(['is_trash' => true]);

        return back()->with('status', 'Class deleted.');
    }
}
