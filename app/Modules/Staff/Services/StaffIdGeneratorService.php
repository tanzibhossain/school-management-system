<?php

namespace App\Modules\Staff\Services;

use App\Modules\Staff\Models\StaffIdConfig;
use Illuminate\Support\Facades\DB;

class StaffIdGeneratorService
{
    /**
     * Generate and reserve the next employee ID for a school.
     * Uses a DB lock to prevent duplicates under concurrent hires.
     */
    public function generate(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId): string {
            /** @var StaffIdConfig $config */
            $config = StaffIdConfig::where('school_id', $schoolId)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['school_id' => $schoolId],
                    [
                        'prefix'          => 'EMP',
                        'include_year'    => true,
                        'year_format'     => 'YYYY',
                        'separator'       => '/',
                        'sequence_length' => 4,
                        'reset_yearly'    => true,
                        'last_sequence'   => 0,
                    ],
                );

            $currentYear = (int) now()->format('Y');

            if ($config->reset_yearly && $config->last_reset_year !== $currentYear) {
                $config->last_sequence   = 0;
                $config->last_reset_year = $currentYear;
            }

            $config->last_sequence += 1;
            $config->save();

            return $this->format($config, $currentYear);
        });
    }

    private function format(StaffIdConfig $config, int $year): string
    {
        $parts = [];

        if ($config->prefix) {
            $parts[] = $config->prefix;
        }

        if ($config->include_year) {
            $parts[] = $config->year_format === 'YY'
                ? substr((string) $year, -2)
                : (string) $year;
        }

        $parts[] = str_pad((string) $config->last_sequence, $config->sequence_length, '0', STR_PAD_LEFT);

        return implode($config->separator, $parts);
    }
}
