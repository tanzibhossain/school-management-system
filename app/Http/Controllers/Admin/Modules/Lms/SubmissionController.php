<?php

namespace App\Http\Controllers\Admin\Modules\Lms;

use App\Modules\LMS\Models\Submission;
use App\Modules\LMS\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SubmissionController extends Controller
{
    public function __construct(private readonly SubmissionService $submissions) {}

    public function grade(Request $request, int $id): RedirectResponse
    {
        $submission = Submission::where('school_id', app('current_school_id'))
            ->with('assignment:id,max_marks')
            ->findOrFail($id);

        $data = $request->validate([
            'marks_awarded' => ['required', 'integer', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->submissions->gradeSubmission($submission, $data['marks_awarded'], $data['feedback'] ?? null);
        } catch (HttpExceptionInterface $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('Submission Graded.'));
    }
}
