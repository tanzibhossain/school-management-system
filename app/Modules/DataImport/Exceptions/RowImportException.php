<?php

namespace App\Modules\DataImport\Exceptions;

use RuntimeException;

/**
 * Thrown by a row importer when one spreadsheet row fails validation or
 * resolution (e.g. "Class 'Grade 5' not found"). Caught per-row by
 * ImportBatchJob — never lets one bad row abort the rest of the batch.
 */
class RowImportException extends RuntimeException
{
    /** @param string[] $messages */
    public function __construct(private readonly array $messages)
    {
        parent::__construct(implode(' ', $messages));
    }

    /** @return string[] */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
