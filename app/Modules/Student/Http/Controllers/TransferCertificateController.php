<?php

namespace App\Modules\Student\Http\Controllers;

use App\Modules\Student\Http\Resources\TransferCertificateResource;
use App\Modules\Student\Http\Resources\TransferCertificateTemplateResource;
use App\Modules\Student\Http\Requests\StoreTcTemplateRequest;
use App\Modules\Student\Http\Requests\UpdateTcTemplateRequest;
use App\Modules\Student\Models\TransferCertificate;
use App\Modules\Student\Models\TransferCertificateTemplate;
use App\Modules\Student\Services\TransferCertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class TransferCertificateController extends Controller
{
    public function __construct(private readonly TransferCertificateService $service) {}

    // ── Templates ──────────────────────────────────────────────────────────────

    public function indexTemplates(): AnonymousResourceCollection
    {
        $templates = TransferCertificateTemplate::where('school_id', app('current_school_id'))
            ->orderByDesc('is_default')
            ->get();

        return TransferCertificateTemplateResource::collection($templates);
    }

    public function storeTemplate(StoreTcTemplateRequest $request): TransferCertificateTemplateResource
    {
        $schoolId = app('current_school_id');
        $data     = array_merge($request->validated(), ['school_id' => $schoolId]);

        if (! empty($data['is_default'])) {
            TransferCertificateTemplate::where('school_id', $schoolId)->update(['is_default' => false]);
        }

        $template = TransferCertificateTemplate::create($data);

        return new TransferCertificateTemplateResource($template);
    }

    public function updateTemplate(UpdateTcTemplateRequest $request, int $id): TransferCertificateTemplateResource
    {
        $schoolId = app('current_school_id');
        $template = TransferCertificateTemplate::where('school_id', $schoolId)->findOrFail($id);

        if (! empty($request->validated()['is_default'])) {
            TransferCertificateTemplate::where('school_id', $schoolId)->update(['is_default' => false]);
        }

        $template->update($request->validated());

        return new TransferCertificateTemplateResource($template->fresh());
    }

    public function destroyTemplate(int $id): JsonResponse
    {
        $template = TransferCertificateTemplate::where('school_id', app('current_school_id'))->findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Template deleted.']);
    }

    // ── Issued Certificates ────────────────────────────────────────────────────

    public function index(int $studentId): AnonymousResourceCollection
    {
        $certs = TransferCertificate::where('school_id', app('current_school_id'))
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get();

        return TransferCertificateResource::collection($certs);
    }

    public function issue(int $id): TransferCertificateResource
    {
        $tc = TransferCertificate::where('school_id', app('current_school_id'))->findOrFail($id);
        $tc = $this->service->issue($tc);

        return new TransferCertificateResource($tc);
    }

    public function preview(int $id): JsonResponse
    {
        $tc      = TransferCertificate::where('school_id', app('current_school_id'))->findOrFail($id);
        $html    = $this->service->render($tc->load(['student.currentAcademic.schoolClass', 'template']));

        return response()->json(['html' => $html]);
    }
}
