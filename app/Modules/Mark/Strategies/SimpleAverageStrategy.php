<?php

namespace App\Modules\Mark\Strategies;

use App\Modules\Mark\Support\GradeResolver;
use Illuminate\Support\Collection;

/**
 * Straight average of grade points across all taken subjects.
 * No optional-subject rules; overall pass requires every subject passed.
 */
class SimpleAverageStrategy implements ResultStrategy
{
    public function calculate(Collection $units, Collection $boundaries): array
    {
        if ($units->isEmpty()) {
            return ['gpa' => 0.00, 'grade' => null, 'is_pass' => false];
        }

        $isPass = ! $units->contains(fn ($u) => ! $u['is_pass']);
        $gpa    = round($units->avg(fn ($u) => (float) $u['gpa_point']), 2);

        return [
            'gpa'     => $gpa,
            'grade'   => GradeResolver::byGpaPoint($boundaries, $gpa),
            'is_pass' => $isPass,
        ];
    }
}
