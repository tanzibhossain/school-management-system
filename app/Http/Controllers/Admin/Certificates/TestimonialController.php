<?php

namespace App\Http\Controllers\Admin\Certificates;

use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Certificate\Services\TestimonialService;
use App\Modules\Student\Models\Student;
use App\Services\PdfRenderingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly TestimonialService $testimonials,
        private readonly PdfRenderingService $pdf,
    ) {}

    public function index(): View
    {
        $schoolId = app('current_school_id');

        return view('admin.certificates.testimonials.index', [
            'testimonials' => Testimonial::where('school_id', $schoolId)->with('student:id,name,student_id')->orderByDesc('id')->limit(500)->get(),
            'students'     => Student::where('school_id', $schoolId)->where('is_trash', false)->orderBy('name')->get(['id', 'name', 'student_id']),
            'templates'    => TestimonialTemplate::where('school_id', $schoolId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = app('current_school_id');

        $data = $request->validate([
            'student_id'     => ['required', 'integer', "exists:students,id,school_id,{$schoolId}"],
            'template_id'    => ['nullable', 'integer', "exists:testimonial_templates,id,school_id,{$schoolId}"],
            'conduct_remark' => ['required', 'string', 'max:500'],
        ], [], ['template_id' => 'template', 'conduct_remark' => 'conduct remark']);

        $student = Student::where('school_id', $schoolId)->findOrFail($data['student_id']);
        $testimonial = $this->testimonials->generate($schoolId, $student, $data, $request->user());
        $this->testimonials->issue($testimonial); // renders + stores the PDF, marks issued

        return back()->with('status', 'Testimonial issued.');
    }

    public function download(int $id): Response
    {
        $testimonial = Testimonial::where('school_id', app('current_school_id'))->findOrFail($id);
        $bytes = $this->pdf->renderToPdf($this->testimonials->render($testimonial));

        return response($bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="testimonial-' . $testimonial->id . '.pdf"',
        ]);
    }
}
