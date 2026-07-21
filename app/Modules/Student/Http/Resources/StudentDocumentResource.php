<?php

namespace App\Modules\Student\Http\Resources;

use App\Modules\Student\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StudentDocument */
class StudentDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'original_name' => $this->original_name,
            'url' => \Storage::disk('minio')->temporaryUrl($this->file_path, now()->addMinutes(30)),
            'uploaded_at' => $this->created_at,
        ];
    }
}
