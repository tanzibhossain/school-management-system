<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Modules\Academic\Models\Subject;
use App\Modules\Academic\Repositories\AcademicRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private readonly AcademicRepository $academic) {}

    public function index(): View
    {
        $subjects = Subject::where('school_id', app('current_school_id'))
            ->where('is_trash', false)
            ->orderBy('name')
            ->get();

        return view('admin.setup.subjects.index', compact('subjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');
        Subject::create($this->validated($request, $schoolId, null) + ['school_id' => $schoolId]);
        $this->academic->flush();

        return back()->with('status', 'Subject created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $subject = Subject::where('school_id', $schoolId)->findOrFail($id);
        $subject->update($this->validated($request, $schoolId, $id));
        $this->academic->flush();

        return back()->with('status', 'Subject updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = app('current_school_id');
        $subject = Subject::where('school_id', $schoolId)->findOrFail($id);
        $subject->update(['is_trash' => true]);
        $this->academic->flush();

        return back()->with('status', 'Subject deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $schoolId, ?int $id): array
    {
        $ignore = $id ?? 'NULL';

        return $request->validate([
            'name' => ['required', 'string', 'max:150', "unique:subjects,name,{$ignore},id,school_id,{$schoolId},is_trash,0"],
            'sub_code' => ['nullable', 'string', 'max:50'],
        ], [], ['sub_code' => 'subject code']);
    }
}
