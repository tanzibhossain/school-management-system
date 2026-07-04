<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Requests\SaveSiteLayoutRequest;
use App\Modules\Website\Http\Resources\SiteLayoutResource;
use App\Modules\Website\Models\SiteLayout;
use App\Modules\Website\Services\SiteLayoutService;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class SiteLayoutController extends Controller
{
    public function __construct(private readonly SiteLayoutService $service) {}

    public function show(string $type): SiteLayoutResource
    {
        $this->validateType($type);

        $layout = $this->service->current(app('current_school_id'), $type);
        abort_if(! $layout, 404);

        return new SiteLayoutResource($layout);
    }

    public function update(SaveSiteLayoutRequest $request, string $type): SiteLayoutResource
    {
        $this->validateType($type);

        $layout = $this->service->save(app('current_school_id'), $type, $request->validated('layout_json'), $request->user());

        return new SiteLayoutResource($layout);
    }

    public function publish(string $type): SiteLayoutResource
    {
        $this->validateType($type);

        $layout = $this->service->publish(app('current_school_id'), $type);

        return new SiteLayoutResource($layout);
    }

    private function validateType(string $type): void
    {
        Validator::make(['type' => $type], ['type' => ['required', Rule::in(SiteLayout::TYPES)]])->validate();
    }
}
