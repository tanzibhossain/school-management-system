<?php

namespace App\Modules\Mark\Strategies;

use App\Modules\Mark\Support\GradeResolver;
use Illuminate\Support\Collection;

/**
 * Marks-weighted: overall percentage = Σ obtained / Σ possible.
 * Subjects with bigger full marks naturally weigh more.
 */
class WeightedAverageStrategy implements ResultStrategy
{
    public function calculate(Collection $units, Collection $boundaries): array
    {
        $possible = $units->sum(fn ($u) => (float) $u['possible']);

        if ($possible <= 0) {
            return ['gpa' => 0.00, 'grade' => null, 'is_pass' => false];
        }

        $obtained = $units->sum(fn ($u) => (float) $u['obtained']);
        $pct      = round(($obtained / $possible) * 100, 2);
        $resolved = GradeResolver::byPercentage($boundaries, $pct);

        return [
            'gpa'     => $resolved['gpa_point'],
            'grade'   => $resolved['grade'],
            'is_pass' => ! $units->contains(fn ($u) => ! $u['is_pass']),
        ];
    }
}
