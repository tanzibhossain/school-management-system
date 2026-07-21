<?php

namespace App\Modules\Mark\Support;

use App\Modules\Mark\Models\GradeBoundary;
use Illuminate\Support\Collection;

class GradeResolver
{
    /**
     * Resolve grade label + gpa point from a percentage.
     * Highest boundary whose min_percent is <= pct wins (tolerates gaps like 79.99 → 80).
     *
     * @param  Collection<int, GradeBoundary>  $boundaries
     * @return array{grade: ?string, gpa_point: ?float}
     */
    public static function byPercentage(Collection $boundaries, float $pct): array
    {
        $match = $boundaries
            ->sortByDesc(fn ($b) => (float) $b->min_percent)
            ->first(fn ($b) => $pct >= (float) $b->min_percent);

        return [
            'grade' => $match?->grade_label,
            'gpa_point' => $match?->gpa_point !== null ? (float) $match->gpa_point : null,
        ];
    }

    /**
     * Resolve grade label from a GPA (used by bd_national for the overall grade).
     * Highest boundary whose gpa_point is <= gpa wins.
     *
     * @param  Collection<int, GradeBoundary>  $boundaries
     */
    public static function byGpaPoint(Collection $boundaries, float $gpa): ?string
    {
        return $boundaries
            ->filter(fn ($b) => $b->gpa_point !== null)
            ->sortByDesc(fn ($b) => (float) $b->gpa_point)
            ->first(fn ($b) => $gpa >= (float) $b->gpa_point)
            ?->grade_label;
    }
}
