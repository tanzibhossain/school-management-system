<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Requests\StorePageTemplateRequest;
use App\Modules\Website\Http\Resources\PageTemplateResource;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Repositories\PageTemplateRepository;
use App\Modules\Website\Services\PageTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PageTemplateController extends Controller
{
    public function __construct(
        private readonly PageTemplateService $service,
        private readonly PageTemplateRepository $repository,
    ) {}

    /** Global starter templates + this school's own saved ones. */
    public function index(): AnonymousResourceCollection
    {
        return PageTemplateResource::collection($this->repository->availableTo(app('current_school_id')));
    }

    public function store(StorePageTemplateRequest $request): JsonResponse
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($request->validated('page_id'));
        $template = $this->service->saveAsTemplate($page, $request->validated('name'));

        return (new PageTemplateResource($template))->response()->setStatusCode(201);
    }
}
