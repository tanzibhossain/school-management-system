<?php

namespace App\Modules\Payroll\Services;

use App\Modules\Payroll\Models\SalaryComponent;
use App\Modules\Payroll\Models\StaffSalaryValue;
use App\Modules\Payroll\Repositories\StaffSalaryValueRepository;
use Illuminate\Support\Collection;

class StaffSalaryValueService
{
    public function __construct(
        private readonly SalaryComponentService $components,
        private readonly StaffSalaryValueRepository $repository,
    ) {}

    /**
     * Every active component for the school, each carrying this staff member's
     * value (0 if never set — "components with no value set show as 0", per spec).
     *
     * @return Collection<int, array{component: SalaryComponent, amount: string}>
     */
    public function breakdown(int $schoolId, int $staffId): Collection
    {
        $values = $this->repository->forStaff($schoolId, $staffId)->keyBy('salary_component_id');

        return $this->components->forSchool($schoolId)->map(fn (SalaryComponent $component) => [
            'component' => $component,
            'amount' => $values->get($component->id)?->amount ?? '0.00',
        ]);
    }

    /** @param array<int, array{component_id: int, amount: numeric-string|float}> $values */
    public function setValues(int $schoolId, int $staffId, array $values): void
    {
        foreach ($values as $value) {
            StaffSalaryValue::updateOrCreate(
                ['school_id' => $schoolId, 'staff_id' => $staffId, 'salary_component_id' => $value['component_id']],
                ['amount' => $value['amount']],
            );
        }
    }

    /**
     * gross = sum(earning components), net = gross - sum(deduction components) — flat,
     * no attendance proration (matches the DevPlan's own calculateGrossAndNet exactly).
     *
     * @return array{gross: float, deductions: float, net: float, lines: array<int, array{label: string, type: string, amount: float}>}
     */
    public function calculateGrossAndNet(int $schoolId, int $staffId): array
    {
        $gross = 0.0;
        $deductions = 0.0;
        $lines = [];

        foreach ($this->breakdown($schoolId, $staffId) as $row) {
            $amount = (float) $row['amount'];
            $lines[] = ['label' => $row['component']->name, 'type' => $row['component']->component_type, 'amount' => $amount];

            if ($row['component']->component_type === 'earning') {
                $gross += $amount;
            } else {
                $deductions += $amount;
            }
        }

        return ['gross' => $gross, 'deductions' => $deductions, 'net' => $gross - $deductions, 'lines' => $lines];
    }
}
