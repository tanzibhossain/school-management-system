<?php

namespace App\Modules\Website\Services;

use App\Modules\Website\Models\SiteSetting;

class SiteSettingService
{
    public function getOrCreate(int $schoolId): SiteSetting
    {
        // ->fresh() forces wasRecentlyCreated=false even on the very first
        // lazy-create, so a GET request never triggers Laravel's automatic
        // 201 (ResourceResponse::calculateStatus()) — a GET must always be 200.
        return SiteSetting::forSchool($schoolId)->fresh();
    }

    /** @param array<string, mixed> $data */
    public function update(int $schoolId, array $data): SiteSetting
    {
        $settings = SiteSetting::forSchool($schoolId);
        $settings->update($data);

        return $settings->fresh();
    }
}
