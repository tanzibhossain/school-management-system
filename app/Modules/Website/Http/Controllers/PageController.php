<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Requests\PublishPageLayoutRequest;
use App\Modules\Website\Http\Requests\SavePageLayoutRequest;
use App\Modules\Website\Http\Requests\StorePageRequest;
use App\Modules\Website\Http\Requests\UpdatePageRequest;
use App\Modules\Website\Http\Resources\PageLayoutResource;
use App\Modules\Website\Http\Resources\PageResource;
use App\Modules\Website\Models\Page;
use App\Modules\Website\Repositories\PageRepository;
use App\Modules\Website\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $service,
        private readonly PageRepository $repository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return PageResource::collection($this->repository->forSchool(app('current_school_id')));
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $page = $this->service->create(app('current_school_id'), $request->validated());

        return (new PageResource($page))->response()->setStatusCode(201);
    }

    public function show(int $id): PageResource
    {
        $page = Page::forSchool(app('current_school_id'))->with(['layouts'])->findOrFail($id);

        return new PageResource($page);
    }

    public function update(UpdatePageRequest $request, int $id): PageResource
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $page = $this->service->update($page, $request->validated());

        return new PageResource($page);
    }

    public function destroy(int $id): JsonResponse
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $page->delete();

        return response()->json(null, 204);
    }

    /** POST /pages/{id}/layout — saves a new (draft) revision. */
    public function saveLayout(SavePageLayoutRequest $request, int $id): JsonResponse
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $layout = $this->service->saveLayout($page, $request->validated('layout_json'), $request->user());

        return (new PageLayoutResource($layout))->response()->setStatusCode(201);
    }

    /** POST /pages/{id}/publish — publishes one revision (defaults to the latest). */
    public function publish(PublishPageLayoutRequest $request, int $id): PageResource
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $page = $this->service->publish($page, $request->validated('layout_id'));

        return new PageResource($page->load('layouts'));
    }

    /** POST /pages/{id}/duplicate. */
    public function duplicate(int $id): JsonResponse
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $copy = $this->service->duplicate($page);

        return (new PageResource($copy))->response()->setStatusCode(201);
    }

    /** GET /pages/{id}/revisions — full version history. */
    public function revisions(int $id): AnonymousResourceCollection
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);

        return PageLayoutResource::collection($page->layouts()->get());
    }

    /** POST /pages/{id}/restore/{lid} — creates a NEW revision copying an old one, never rewinds history. */
    public function restore(int $id, int $lid): JsonResponse
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $revision = $page->layouts()->findOrFail($lid);

        $restored = $this->service->restore($page, $revision, request()->user());

        return (new PageLayoutResource($restored))->response()->setStatusCode(201);
    }

    /** POST /pages/{id}/set-homepage. */
    public function setHomepage(int $id): PageResource
    {
        $page = Page::forSchool(app('current_school_id'))->findOrFail($id);
        $page = $this->service->setHomepage($page);

        return new PageResource($page);
    }
}
