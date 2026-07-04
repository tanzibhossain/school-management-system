<?php

namespace App\Modules\School\Services;

use App\Modules\School\Models\ModuleSetting;
use App\Modules\School\Repositories\ModuleSettingRepository;
use Illuminate\Support\Collection;

class ModuleSettingService
{
    public function __construct(
        private readonly ModuleSettingRepository $repository,
    ) {}

    /**
     * Every known optional module, merged with this school's stored rows.
     * A module with no row yet is reported disabled (default false) rather
     * than omitted, so the admin settings screen always shows the full list.
     *
     * @return Collection<int, array{module: string, is_enabled: bool}>
     */
    public function allForSchool(int $schoolId): Collection
    {
        $rows = $this->repository->forSchool($schoolId)->keyBy('module');

        return collect(ModuleSetting::MODULES)->map(fn (string $module) => [
            'module' => $module,
            'is_enabled' => (bool) ($rows->get($module)?->is_enabled ?? false),
        ]);
    }

    public function isEnabled(int $schoolId, string $module): bool
    {
        return (bool) $this->repository->findForModule($schoolId, $module)?->is_enabled;
    }

    public function setEnabled(int $schoolId, string $module, bool $enabled): ModuleSetting
    {
        $setting = ModuleSetting::forSchool($schoolId)->where('module', $module)->first();

        if ($setting) {
            return $this->repository->update($setting, ['is_enabled' => $enabled]);
        }

        return $this->repository->create([
            'school_id' => $schoolId,
            'module' => $module,
            'is_enabled' => $enabled,
        ]);
    }
}
