<?php

namespace App\Modules\Website\Services;

use App\Models\User;
use App\Modules\Website\Models\WebsiteMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class WebsiteMediaService
{
    public function upload(int $schoolId, UploadedFile $file, ?User $user): WebsiteMedia
    {
        $path = $file->store("website/{$schoolId}/media", 'minio');

        $dimensions = $this->imageDimensions($file);

        return WebsiteMedia::create([
            'school_id' => $schoolId,
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'width_px' => $dimensions[0] ?? null,
            'height_px' => $dimensions[1] ?? null,
            'uploaded_by' => $user?->id,
        ]);
    }

    public function delete(WebsiteMedia $media): void
    {
        Storage::disk('minio')->delete($media->path);
        $media->delete();
    }

    public function updateAltText(WebsiteMedia $media, ?string $altText): WebsiteMedia
    {
        $media->update(['alt_text' => $altText]);

        return $media;
    }

    /** @return array{0: int, 1: int}|null */
    private function imageDimensions(UploadedFile $file): ?array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            return null;
        }

        $size = @getimagesize($file->getRealPath());

        return $size ? [$size[0], $size[1]] : null;
    }
}
