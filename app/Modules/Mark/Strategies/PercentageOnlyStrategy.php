<?php

namespace App\Modules\Mark\Strategies;

use App\Modules\Mark\Support\GradeResolver;
use Illuminate\Support\Collection;

/**
 * No GPA at all — percentage plus an optional label (Pass/Fail template).
 */
class PercentageOnlyStrategy implements ResultStrategy
{
    public function calculate(Collection $units, Collection $boundaries): array
    {
        $possible = $units->sum(fn ($u) => (float) $u['possible']);
        $pct = $possible > 0 ? round(($units->sum(fn ($u) => (float) $u['obtained']) / $possible) * 100, 2) : 0.0;

        return [
            'gpa' => null,
            'grade' => GradeResolver::byPercentage($boundaries, $pct)['grade'],
            'is_pass' => $units->isNotEmpty() && ! $units->contains(fn ($u) => ! $u['is_pass']),
        ];
    }
}
