<?php

namespace App\Modules\DataImport\Services\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Excel/CSV date cells arrive two ways depending on how the source file was
 * authored: a numeric Excel serial date (when typed into a real spreadsheet
 * cell formatted as a date) or a plain text string (typical for CSV, or a
 * text-formatted spreadsheet cell). Handles both rather than assuming one.
 */
trait ParsesExcelDates
{
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            }

            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
