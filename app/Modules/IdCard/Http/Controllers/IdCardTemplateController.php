<?php

namespace App\Modules\IdCard\Http\Controllers;

use App\Modules\IdCard\Http\Requests\StoreIdCardTemplateRequest;
use App\Modules\IdCard\Http\Requests\UpdateIdCardTemplateRequest;
use App\Modules\IdCard\Http\Resources\IdCardTemplateResource;
use App\Modules\IdCard\Models\IdCardTemplate;
use App\Modules\IdCard\Repositories\IdCardTemplateRepository;
use App\Modules\IdCard\Services\IdCardTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class IdCardTemplateController extends Controller
{
    public function __construct(
        private readonly IdCardTemplateService $service,
        private readonly IdCardTemplateRepository $repository,
    ) {}

    /** GET /v2/id-cards/templates?type=student|staff */
    public function index(Request $request): AnonymousResourceCollection
    {
        $schoolId = app('current_school_id');

        $templates = $request->filled('type')
            ? $this->repository->ofType($schoolId, $request->string('type')->toString())
            : $this->service->all($schoolId);

        return IdCardTemplateResource::collection($templates);
    }

    public function store(StoreIdCardTemplateRequest $request): IdCardTemplateResource
    {
        $schoolId = app('current_school_id');
        $data = $request->validated() + ['school_id' => $schoolId];

        if (! empty($data['is_default'])) {
            IdCardTemplate::forSchool($schoolId)->ofType($data['type'])->update(['is_default' => false]);
        }

        return new IdCardTemplateResource($this->service->create($data));
    }

    public function update(UpdateIdCardTemplateRequest $request, int $id): IdCardTemplateResource
    {
        $schoolId = app('current_school_id');
        $template = $this->service->findOrFail($id, $schoolId);

        if (! empty($request->validated()['is_default'])) {
            IdCardTemplate::forSchool($schoolId)->ofType($template->type)->update(['is_default' => false]);
        }

        return new IdCardTemplateResource($this->service->update($template, $request->validated()));
    }

    public function destroy(int $id): JsonResponse
    {
        $schoolId = app('current_school_id');
        $this->service->delete($this->service->findOrFail($id, $schoolId));

        return response()->json(['message' => 'Template deleted.']);
    }
}
