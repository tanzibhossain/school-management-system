<?php

namespace App\Http\Controllers\Admin\Academics;

use App\Modules\Examination\Models\ExamType;
use App\Modules\Examination\Services\ExaminationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ExamTypeController extends Controller
{
    public function __construct(private readonly ExaminationService $exams) {}

    public function index(): View
    {
        $types = ExamType::where('school_id', app('current_school_id'))
            ->withCount('exams')
            ->orderBy('name')
            ->get();

        return view('admin.academics.exam-types.index', compact('types'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->exams->createType(app('current_school_id'), $this->validated($request));

        return back()->with('status', 'Exam type created.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $type = ExamType::where('school_id', app('current_school_id'))->findOrFail($id);
        $this->exams->updateType($type, $this->validated($request));

        return back()->with('status', 'Exam type updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $type = ExamType::where('school_id', app('current_school_id'))->withCount('exams')->findOrFail($id);

        if ($type->exams_count > 0) {
            return back()->with('error', 'Cannot delete an exam type that has exams.');
        }

        $type->delete();

        return back()->with('status', 'Exam type deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
