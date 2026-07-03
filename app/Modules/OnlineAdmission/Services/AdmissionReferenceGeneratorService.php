<?php

namespace App\Modules\OnlineAdmission\Services;

use App\Modules\OnlineAdmission\Models\AdmissionApplication;
use Illuminate\Support\Facades\DB;

/**
 * Generates a human-readable, per-school-scoped reference number
 * (APP-2026-000123) an applicant can use to check their status later —
 * they have no login. Simpler than Student's StudentIdGeneratorService
 * (no dedicated config table): a locked count-per-year scoped to the
 * school, with a collision-retry loop as a belt-and-braces guard.
 */
class AdmissionReferenceGeneratorService
{
    public function generate(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId): string {
            $year = (int) now()->format('Y');

            $sequence = AdmissionApplication::where('school_id', $schoolId)
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->count() + 1;

            $reference = $this->format($year, $sequence);

            while (AdmissionApplication::where('school_id', $schoolId)->where('reference_number', $reference)->exists()) {
                $sequence++;
                $reference = $this->format($year, $sequence);
            }

            return $reference;
        });
    }

    private function format(int $year, int $sequence): string
    {
        return sprintf('APP-%d-%06d', $year, $sequence);
    }
}
