<?php

namespace App\Modules\DataImport\Services;

use App\Models\User;
use App\Modules\DataImport\Jobs\ImportBatchJob;
use App\Modules\DataImport\Models\ImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stores the uploaded spreadsheet and kicks off the queued job. Mirrors
 * IdCardBatchService/SmsBatchService's request() shape: create the batch row
 * as 'queued', dispatch, then return a fresh()-refetched instance since
 * QUEUE_CONNECTION=sync (tests, or any deployment without Horizon running)
 * has already run the job to completion by the time dispatch() returns.
 */
class ImportBatchService
{
    public function request(int $schoolId, string $type, UploadedFile $file, User $user): ImportBatch
    {
        $path = $file->store("imports/{$schoolId}/{$type}", 'minio');

        $batch = ImportBatch::create([
            'school_id' => $schoolId,
            'type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'status' => 'queued',
            'requested_by' => $user->id,
        ]);

        ImportBatchJob::dispatch($batch->id);

        return $batch->fresh();
    }
}
