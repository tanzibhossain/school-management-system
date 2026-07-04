<?php

namespace App\Modules\Website\Http\Controllers;

use App\Modules\Website\Http\Requests\UpdateSiteSettingRequest;
use App\Modules\Website\Http\Resources\SiteSettingResource;
use App\Modules\Website\Services\SiteSettingService;
use Illuminate\Routing\Controller;

class SiteSettingController extends Controller
{
    public function __construct(private readonly SiteSettingService $service) {}

    public function show(): SiteSettingResource
    {
        return new SiteSettingResource($this->service->getOrCreate(app('current_school_id')));
    }

    public function update(UpdateSiteSettingRequest $request): SiteSettingResource
    {
        $settings = $this->service->update(app('current_school_id'), $request->validated());

        return new SiteSettingResource($settings);
    }
}
