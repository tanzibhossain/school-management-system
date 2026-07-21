<?php

namespace App\Modules\Student\Services;

use App\Modules\Student\Models\StudentIdConfig;
use Illuminate\Support\Facades\DB;

class StudentIdGeneratorService
{
    /**
     * Generate and reserve the next student ID for a school.
     * Uses a DB lock to prevent duplicate IDs under concurrent admissions.
     */
    public function generate(int $schoolId): string
    {
        return DB::transaction(function () use ($schoolId): string {
            /** @var StudentIdConfig $config */
            $config = StudentIdConfig::where('school_id', $schoolId)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['school_id' => $schoolId],
                    [
                        'prefix' => 'STU',
                        'include_year' => true,
                        'year_format' => 'YYYY',
                        'separator' => '/',
                        'sequence_length' => 4,
                        'reset_yearly' => true,
                        'last_sequence' => 0,
                    ],
                );

            $currentYear = (int) now()->format('Y');

            // Reset sequence if reset_yearly and we're in a new year
            if ($config->reset_yearly && $config->last_reset_year !== $currentYear) {
                $config->last_sequence = 0;
                $config->last_reset_year = $currentYear;
            }

            $config->last_sequence += 1;
            $config->save();

            return $this->format($config, $currentYear);
        });
    }

    private function format(StudentIdConfig $config, int $year): string
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
