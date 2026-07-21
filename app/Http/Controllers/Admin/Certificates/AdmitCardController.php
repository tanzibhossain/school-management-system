<?php

namespace App\Http\Controllers\Admin\Certificates;

use App\Modules\Certificate\Models\AdmitCard;
use App\Modules\Certificate\Services\AdmitCardService;
use App\Modules\Examination\Models\Exam;
use App\Modules\Student\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdmitCardController extends Controller
{
    public function __construct(private readonly AdmitCardService $admitCards) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.certificates.admit-cards.index', [
            'cards' => AdmitCard::where('school_id', $schoolId)->with(['student:id,name,student_id', 'exam:id,title'])->orderByDesc('id')->limit(500)->get(),
            'students' => Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'student_id']),
            'exams' => Exam::where('school_id', $schoolId)->orderByDesc('id')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'student_id' => ['required', 'integer', "exists:students,id,school_id,{$schoolId}"],
            'exam_id' => ['required', 'integer', "exists:exams,id,school_id,{$schoolId}"],
        ]);

        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);
        $exam = Exam::where('school_id', $schoolId)->findOrFail($data['exam_id']);
        $this->admitCards->generate($schoolId, $student, $exam, $request->user());

        return back()->with('status', __('Admit card generated.'));
    }

    public function download(int $id): Response
    {
        $card = AdmitCard::where('school_id', app('current_school_id'))->findOrFail($id);
        abort_unless($card->file_path && Storage::disk('minio')->exists($card->file_path), 404);

        return response(Storage::disk('minio')->get($card->file_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="admit-card-'.$card->id.'.pdf"',
        ]);
    }
}
