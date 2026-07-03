<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Shared HTML-to-PDF rendering, used across modules (Certificate's Admit Card
 * and Testimonial, and retrofitted into Student's Transfer Certificate).
 * Lives at the top level (alongside BaseService/BaseRepository) rather than
 * inside any one module, since Certificate depends on Student — not the
 * reverse — and both need this.
 */
class PdfRenderingService
{
    /** Render an HTML string to raw PDF bytes. */
    public function renderToPdf(string $html): string
    {
        return Pdf::loadHTML($html)->output();
    }

    /** Store raw bytes on a disk (defaults to 'minio', same disk every module uses for uploads). */
    public function store(string $bytes, string $path, string $disk = 'minio'): string
    {
        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    /** Render HTML straight to a stored PDF file in one call. */
    public function generateAndStore(string $html, string $path, string $disk = 'minio'): string
    {
        return $this->store($this->renderToPdf($html), $path, $disk);
    }
}
