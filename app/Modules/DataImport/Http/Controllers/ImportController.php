<?php

namespace App\Modules\DataImport\Http\Controllers;

use App\Modules\DataImport\Exports\StaffImportTemplateExport;
use App\Modules\DataImport\Exports\StudentImportTemplateExport;
use App\Modules\DataImport\Http\Requests\RequestImportRequest;
use App\Modules\DataImport\Http\Resources\ImportBatchResource;
use App\Modules\DataImport\Models\ImportBatch;
use App\Modules\DataImport\Repositories\ImportBatchRepository;
use App\Modules\DataImport\Services\ImportBatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function __construct(
        private readonly ImportBatchService $service,
        private readonly ImportBatchRepository $repository,
    ) {}

    /** POST /v2/data-imports — upload a spreadsheet, queues the import job. */
    public function store(RequestImportRequest $request): JsonResponse
    {
        $schoolId = app('current_school_id');
        $data = $request->validated();

        $batch = $this->service->request($schoolId, $data['type'], $request->file('file'), $request->user());

        // service->request() returns a fresh()-refetched instance, so wasRecentlyCreated
        // is false and the automatic 201 status doesn't kick in — set it explicitly
        // (same reasoning as Sms/IdCard's batch controllers).
        return (new ImportBatchResource($batch))->response()->setStatusCode(201);
    }

    /** GET /v2/data-imports — this school's import history. */
    public function index(): AnonymousResourceCollection
    {
        return ImportBatchResource::collection(
            $this->repository->forSchool(app('current_school_id'))
        );
    }

    /** GET /v2/data-imports/{id} — poll a batch's status and report. */
    public function show(int $id): ImportBatchResource
    {
        $batch = ImportBatch::forSchool(app('current_school_id'))->findOrFail($id);

        return new ImportBatchResource($batch);
    }

    /** GET /v2/data-imports/template?type=student|staff — downloadable sample sheet. */
    public function template(Request $request): BinaryFileResponse
    {
        $data = Validator::make($request->query(), [
            'type' => ['required', Rule::in(ImportBatch::TYPES)],
        ])->validate();

        return $data['type'] === 'student'
            ? Excel::download(new StudentImportTemplateExport(), 'student-import-template.xlsx')
            : Excel::download(new StaffImportTemplateExport(), 'staff-import-template.xlsx');
    }
}
