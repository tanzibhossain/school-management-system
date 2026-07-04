<?php

namespace App\Modules\Website\Services;

use App\Models\User;
use App\Modules\Website\Models\SiteLayout;
use Illuminate\Support\Facades\DB;

/** Same versioning pattern as PageService's layout handling, keyed by type (header/footer) instead of page. */
class SiteLayoutService
{
    public function save(int $schoolId, string $type, array $layoutJson, ?User $user): SiteLayout
    {
        return SiteLayout::create([
            'school_id' => $schoolId,
            'type' => $type,
            'layout_json' => $layoutJson,
            'is_published' => false,
            'created_by' => $user?->id,
        ]);
    }

    public function publish(int $schoolId, string $type, ?int $layoutId = null): SiteLayout
    {
        return DB::transaction(function () use ($schoolId, $type, $layoutId): SiteLayout {
            $query = SiteLayout::forSchool($schoolId)->ofType($type);

            $target = $layoutId
                ? $query->findOrFail($layoutId)
                : $query->orderByDesc('created_at')->firstOrFail();

            SiteLayout::forSchool($schoolId)->ofType($type)->where('is_published', true)
                ->update(['is_published' => false]);

            $target->update(['is_published' => true, 'published_at' => now()]);

            return $target->fresh();
        });
    }

    public function current(int $schoolId, string $type): ?SiteLayout
    {
        return SiteLayout::forSchool($schoolId)->ofType($type)->orderByDesc('created_at')->first();
    }

    public function published(int $schoolId, string $type): ?SiteLayout
    {
        return SiteLayout::forSchool($schoolId)->ofType($type)->where('is_published', true)->first();
    }
}
