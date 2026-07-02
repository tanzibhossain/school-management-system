<?php

namespace App\Modules\Mark\Strategies;

use Illuminate\Support\Collection;

/**
 * Pluggable per-class result calculation (like seating strategies).
 *
 * Input: one entry per SUBJECT UNIT (combined groups already merged), each:
 *   ['is_optional' => bool, 'is_absent' => bool, 'not_applicable' => bool,
 *    'is_pass' => bool, 'gpa_point' => ?float, 'percentage' => float,
 *    'obtained' => float, 'possible' => float]
 *
 * Output: ['gpa' => ?float, 'grade' => ?string, 'is_pass' => bool]
 */
interface ResultStrategy
{
    /**
     * @param  Collection<int, array<string, mixed>>  $units      applicable subject units (N/A excluded)
     * @param  Collection<int, \App\Modules\Mark\Models\GradeBoundary>  $boundaries
     */
    public function calculate(Collection $units, Collection $boundaries): array;
}
