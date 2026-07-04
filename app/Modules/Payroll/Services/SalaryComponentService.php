<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\Payroll\Repositories\SalaryComponentRepository;
use Illuminate\Database\Eloquent\Collection;

class SalaryComponentService
{
    public function __construct(private readonly SalaryComponentRepository $repository) {}

    /** Lazily seeds config('payroll.default_components') the first time a school has none. */
    public function forSchool(int $schoolId): Collection
    {
        $this->ensureDefaults($schoolId);

        return $this->repository->forSchool($schoolId);
    }

    public function create(int $schoolId, array $data): SalaryComponent
    {
        return SalaryComponent::create(array_merge($data, ['school_id' => $schoolId]));
    }

    public function update(SalaryComponent $component, array $data): SalaryComponent
    {
        $component->update($data);

        return $component->fresh();
    }

    /** Soft "remove" — never hard-deletes, so historic staff_salary_values/breakdown snapshots stay meaningful. */
    public function trash(SalaryComponent $component): SalaryComponent
    {
        $component->update(['is_trash' => true]);

        return $component->fresh();
    }

    private function ensureDefaults(int $schoolId): void
    {
        if (SalaryComponent::forSchool($schoolId)->exists()) {
            return;
        }

        foreach (config('payroll.default_components', []) as $default) {
            SalaryComponent::create(array_merge($default, [
                'school_id' => $schoolId,
                'is_default' => true,
            ]));
        }
    }
}
