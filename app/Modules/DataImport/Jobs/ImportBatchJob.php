<?php

namespace App\Modules\DataImport\Jobs;

use App\Modules\DataImport\Exceptions\RowImportException;
use App\Modules\DataImport\Imports\RowCollectionImport;
use App\Modules\DataImport\Models\ImportBatch;
use App\Modules\DataImport\Services\StaffImportRowService;
use App\Modules\DataImport\Services\StudentImportRowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Same shape as GenerateIdCardBatchJob/SendSmsBatchJob (Horizon ShouldQueue,
 * sync in tests): reads the stored spreadsheet, validates+creates one row at
 * a time via the reused Student/Staff services, and never lets one bad row
 * abort the rest — each row is its own try/catch, and only a whole-file
 * problem (unreadable file, etc.) fails the batch itself.
 */
class ImportBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $batchId) {}

    public function handle(StudentImportRowService $studentImporter, StaffImportRowService $staffImporter): void
    {
        $batch = ImportBatch::findOrFail($this->batchId);
        $batch->update(['status' => 'processing']);

        $tmpPath = null;

        try {
            $bytes = Storage::disk('minio')->get($batch->stored_path);
            $extension = pathinfo($batch->stored_path, PATHINFO_EXTENSION) ?: 'xlsx';
            $tmpPath = tempnam(sys_get_temp_dir(), 'import_').'.'.$extension;
            file_put_contents($tmpPath, $bytes);

            $reader = new RowCollectionImport;
            Excel::import($reader, $tmpPath);
            $rows = $reader->rows ?? collect();

            $totalRows = 0;
            $successCount = 0;
            $skippedCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                $totalRows++;
                $rowNumber = $index + 2; // +1 for zero-index, +1 for the header row.

                try {
                    if ($batch->type === 'student') {
                        $studentImporter->import($batch->school_id, $row->toArray());
                    } else {
                        $staffImporter->import($batch->school_id, $row->toArray());
                    }
                    $successCount++;
                } catch (RowImportException $e) {
                    $skippedCount++;
                    $errors[] = ['row' => $rowNumber, 'messages' => $e->getMessages()];
                } catch (Throwable $e) {
                    $skippedCount++;
                    $errors[] = ['row' => $rowNumber, 'messages' => [$e->getMessage()]];
                }
            }

            $batch->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Swallowed (not rethrown): the batch row's status/error_message is the
            // client-facing failure signal, same reasoning as IdCard/Sms's jobs —
            // rethrowing would propagate into the HTTP request under the sync
            // queue driver used in tests.
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        } finally {
            if ($tmpPath && file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }
    }
}
