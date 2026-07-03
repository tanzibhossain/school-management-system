<?php

namespace App\Modules\DataImport\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Reads the first sheet into associative rows keyed by header (Maatwebsite
 * auto-slugs headers to snake_case, e.g. "Admission Number" -> "admission_number",
 * so the downloadable sample templates' human-readable headers map straight
 * onto the row-service field names without any manual column mapping).
 * Same reader class for both student and staff sheets — the row shape
 * differs, but reading rows into a keyed collection doesn't.
 */
class RowCollectionImport implements ToCollection, WithHeadingRow
{
    /** @var Collection<int, Collection<string, mixed>>|null */
    public ?Collection $rows = null;

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
