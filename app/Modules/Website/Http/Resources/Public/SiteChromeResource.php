<?php

namespace App\Modules\Website\Http\Resources\Public;

use App\Modules\Website\Http\Resources\SiteLayoutResource;
use App\Modules\Website\Http\Resources\SiteSettingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the plain {header, footer, settings} array PublicPortalService::siteChrome()
 * returns — same "resource wraps a plain array via $this->resource['key']" pattern
 * Report's resources already use, since this isn't backed by a single Eloquent model.
 */
class SiteChromeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'header' => $this->resource['header'] ? new SiteLayoutResource($this->resource['header']) : null,
            'footer' => $this->resource['footer'] ? new SiteLayoutResource($this->resource['footer']) : null,
            'settings' => new SiteSettingResource($this->resource['settings']),
        ];
    }
}
