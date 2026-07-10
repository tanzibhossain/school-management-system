<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\AcademicYear;
use App\Modules\Academic\Services\AcademicService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function __construct(private readonly AcademicService $academic) {}

    public function index(): View
    {
        $years = AcademicYear::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderByDesc('is_current')
            ->orderByDesc('year')
            ->get();

        return view('admin.setup.academic-years.index', compact('years'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validate([
            'year' => ['required', 'string', 'max:50', "unique:academic_years,year,NULL,id,school_id,{$schoolId},is_trash,0"],
        ], [], ['year' => 'academic year']);

        $this->academic->create($data + ['school_id' => $schoolId, 'is_current' => false]);

        return back()->with('status', 'Academic year created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $year = AcademicYear::where('school_id', $schoolId)->findOrFail($id);
        $data = $request->validate([
            'year' => ['required', 'string', 'max:50', "unique:academic_years,year,{$id},id,school_id,{$schoolId},is_trash,0"],
        ], [], ['year' => 'academic year']);

        $this->academic->update($year, $data);

        return back()->with('status', 'Academic year updated.');
    }

    public function setCurrent(int $id): RedirectResponse
    {
        $this->academic->setCurrentYear(app('current_school_id'), $id);

        return back()->with('status', 'Current academic year updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $year = AcademicYear::where('school_id', app('current_school_id'))->findOrFail($id);

        if ($year->is_current) {
            return back()->with('error', 'Cannot delete the current academic year. Set another year as current first.');
        }

        $this->academic->trash($year);

        return back()->with('status', 'Academic year deleted.');
    }
}
