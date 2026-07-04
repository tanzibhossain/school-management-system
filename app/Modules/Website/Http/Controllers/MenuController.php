<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Requests\ReplaceMenuItemsRequest;
use App\Modules\Website\Http\Requests\StoreMenuRequest;
use App\Modules\Website\Http\Requests\UpdateMenuRequest;
use App\Modules\Website\Http\Resources\MenuResource;
use App\Modules\Website\Models\Menu;
use App\Modules\Website\Repositories\MenuRepository;
use App\Modules\Website\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class MenuController extends Controller
{
    public function __construct(
        private readonly MenuService $service,
        private readonly MenuRepository $repository,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return MenuResource::collection($this->repository->forSchool(app('current_school_id')));
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $menu = Menu::create(array_merge($request->validated(), ['school_id' => app('current_school_id')]));

        return (new MenuResource($menu))->response()->setStatusCode(201);
    }

    public function show(int $id): MenuResource
    {
        $menu = $this->repository->withItems(app('current_school_id'), $id);
        abort_if(! $menu, 404);

        return new MenuResource($menu);
    }

    public function update(UpdateMenuRequest $request, int $id): MenuResource
    {
        $menu = Menu::forSchool(app('current_school_id'))->findOrFail($id);
        $menu->update($request->validated());

        return new MenuResource($menu);
    }

    public function destroy(int $id): JsonResponse
    {
        $menu = Menu::forSchool(app('current_school_id'))->findOrFail($id);
        $menu->delete();

        return response()->json(null, 204);
    }

    /** PUT /menus/{id}/items — full tree replace. */
    public function replaceItems(ReplaceMenuItemsRequest $request, int $id): MenuResource
    {
        $menu = Menu::forSchool(app('current_school_id'))->findOrFail($id);
        $menu = $this->service->replaceItems($menu, $request->validated('items'));

        return new MenuResource($menu);
    }
}
