<?php

namespace App\Modules\DataImport\Http\Resources;

use App\Modules\DataImport\Models\ImportBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ImportBatch */
class ImportBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'original_filename' => $this->original_filename,
            'total_rows' => $this->total_rows,
            'success_count' => $this->success_count,
            'skipped_count' => $this->skipped_count,
            'errors' => $this->errors ?? [],
            'error_message' => $this->error_message,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
