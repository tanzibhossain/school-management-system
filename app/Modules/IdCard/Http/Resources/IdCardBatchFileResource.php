<?php

namespace App\Modules\IdCard\Http\Resources;

use App\Modules\IdCard\Models\IdCardBatchFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin IdCardBatchFile */
class IdCardBatchFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'file_index' => $this->file_index,
            'card_count' => $this->card_count,
            'file_url' => Storage::disk('minio')->temporaryUrl($this->file_path, now()->addMinutes(30)),
        ];
    }
}
