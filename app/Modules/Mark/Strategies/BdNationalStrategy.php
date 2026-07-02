<?php

namespace App\Modules\Mark\Strategies;

use App\Modules\Mark\Support\GradeResolver;
use Illuminate\Support\Collection;

/**
 * Bangladesh national result rules — ALL BD-specific logic lives here:
 * - fail-one-fail-all (any compulsory subject failed/absent → overall fail, GPA 0.00)
 * - optional (4th) subject bonus: GPA = (Σ compulsory GP + max(0, optional GP − 2.00)) / compulsory count
 * - GPA capped at 5.00
 */
class BdNationalStrategy implements ResultStrategy
{
    private const OPTIONAL_BONUS_THRESHOLD = 2.00;

    private const GPA_CAP = 5.00;

    public function calculate(Collection $units, Collection $boundaries): array
    {
        $compulsory = $units->where('is_optional', false);
        $optional   = $units->where('is_optional', true);

        if ($compulsory->isEmpty()) {
            return ['gpa' => 0.00, 'grade' => null, 'is_pass' => false];
        }

        // Fail-one-fail-all: any compulsory failure (incl. absent) fails the exam
        if ($compulsory->contains(fn ($u) => ! $u['is_pass'])) {
            $failLabel = GradeResolver::byGpaPoint($boundaries, 0.0) ?? 'F';

            return ['gpa' => 0.00, 'grade' => $failLabel, 'is_pass' => false];
        }

        $compulsorySum   = $compulsory->sum(fn ($u) => (float) $u['gpa_point']);
        $compulsoryCount = $compulsory->count();

        // Optional bonus — only the highest PASSED optional counts
        $bonus = 0.0;
        $bestOptional = $optional->where('is_pass', true)->max(fn ($u) => (float) $u['gpa_point']);
        if ($bestOptional !== null && $bestOptional > self::OPTIONAL_BONUS_THRESHOLD) {
            $bonus = $bestOptional - self::OPTIONAL_BONUS_THRESHOLD;
        }

        $gpa = round(min(self::GPA_CAP, ($compulsorySum + $bonus) / $compulsoryCount), 2);

        return [
            'gpa'     => $gpa,
            'grade'   => GradeResolver::byGpaPoint($boundaries, $gpa),
            'is_pass' => true,
        ];
    }
}
