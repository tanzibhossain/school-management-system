<?php

namespace App\Modules\Certificate\Http\Controllers;

use App\Modules\Certificate\Http\Requests\GenerateTestimonialRequest;
use App\Modules\Certificate\Http\Requests\StoreTestimonialTemplateRequest;
use App\Modules\Certificate\Http\Requests\UpdateTestimonialTemplateRequest;
use App\Modules\Certificate\Http\Resources\TestimonialResource;
use App\Modules\Certificate\Http\Resources\TestimonialTemplateResource;
use App\Modules\Certificate\Models\Testimonial;
use App\Modules\Certificate\Models\TestimonialTemplate;
use App\Modules\Certificate\Repositories\TestimonialRepository;
use App\Modules\Certificate\Services\TestimonialService;
use App\Modules\Certificate\Services\TestimonialTemplateService;
use App\Modules\Student\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly TestimonialService $service,
        private readonly TestimonialTemplateService $templateService,
        private readonly TestimonialRepository $repository,
    ) {}

    // ── Templates ──────────────────────────────────────────────────────────────

    public function indexTemplates(): AnonymousResourceCollection
    {
        return TestimonialTemplateResource::collection(
            $this->templateService->all(app('current_school_id'))
        );
    }

    public function storeTemplate(StoreTestimonialTemplateRequest $request): TestimonialTemplateResource
    {
        $schoolId = app('current_school_id');
        $data     = $request->validated() + ['school_id' => $schoolId];

        if (! empty($data['is_default'])) {
            TestimonialTemplate::forSchool($schoolId)->update(['is_default' => false]);
        }

        return new TestimonialTemplateResource($this->templateService->create($data));
    }

    public function updateTemplate(UpdateTestimonialTemplateRequest $request, int $id): TestimonialTemplateResource
    {
        $schoolId = app('current_school_id');
        $template = $this->templateService->findOrFail($id, $schoolId);

        if (! empty($request->validated()['is_default'])) {
            TestimonialTemplate::forSchool($schoolId)->update(['is_default' => false]);
        }

        return new TestimonialTemplateResource($this->templateService->update($template, $request->validated()));
    }

    public function destroyTemplate(int $id): JsonResponse
    {
        $schoolId = app('current_school_id');
        $this->templateService->delete($this->templateService->findOrFail($id, $schoolId));

        return response()->json(['message' => 'Template deleted.']);
    }

    // ── Testimonials ───────────────────────────────────────────────────────────

    /** POST /v2/certificates/testimonials/{studentId} */
    public function store(GenerateTestimonialRequest $request, int $studentId): TestimonialResource
    {
        $schoolId = app('current_school_id');
        $student  = Student::where('school_id', $schoolId)->findOrFail($studentId);

        $testimonial = $this->service->generate($schoolId, $student, $request->validated(), $request->user());

        return new TestimonialResource($testimonial);
    }

    /** GET /v2/certificates/testimonials/{studentId} */
    public function index(int $studentId): AnonymousResourceCollection
    {
        return TestimonialResource::collection(
            $this->repository->forStudent(app('current_school_id'), $studentId)
        );
    }

    /** POST /v2/certificates/testimonials/{id}/issue — generates the PDF and marks it issued. */
    public function issue(int $id): TestimonialResource
    {
        $testimonial = Testimonial::forSchool(app('current_school_id'))->findOrFail($id);

        return new TestimonialResource($this->service->issue($testimonial));
    }

    /** GET /v2/certificates/testimonials/{id}/preview */
    public function preview(int $id): JsonResponse
    {
        $testimonial = Testimonial::forSchool(app('current_school_id'))->findOrFail($id);
        $html        = $this->service->render($testimonial);

        return response()->json(['html' => $html]);
    }
}
