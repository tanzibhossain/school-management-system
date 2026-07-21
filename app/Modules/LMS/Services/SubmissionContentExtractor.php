<?php

namespace App\Modules\LMS\Services;

use RuntimeException;
use ZipArchive;

/**
 * Extracts plain text from a submission file so it can be sent to the AI
 * checker. Handles .txt natively and .docx via ZipArchive (a .docx is a zip
 * of XML — reading word/document.xml and stripping tags avoids pulling in a
 * new composer dependency for something this codebase can't install/test
 * mid-session anyway).
 *
 * .pdf is a documented gap: no PDF text-extraction package exists in
 * composer.json (barryvdh/laravel-dompdf only *writes* PDFs, it can't read
 * one back), and adding one requires a `composer require` this session can't
 * run. A PDF submission still gets created and stored fine — only the AI
 * check for it fails with a clear message, which is exactly the
 * "AI checker failure never blocks the submission flow" behavior the DevPlan
 * asks for anyway.
 */
class SubmissionContentExtractor
{
    public function extract(string $absolutePath, string $extension): string
    {
        return match (strtolower($extension)) {
            'txt' => file_get_contents($absolutePath) ?: '',
            'docx' => $this->extractDocx($absolutePath),
            'pdf' => throw new RuntimeException(
                'PDF text extraction is not supported yet — no PDF-parsing package is installed. '
                .'Add one (e.g. smalot/pdfparser) and implement this branch to enable AI checking for PDF submissions.'
            ),
            default => throw new RuntimeException("Unsupported file type for AI text extraction: .{$extension}"),
        };
    }

    private function extractDocx(string $absolutePath): string
    {
        $zip = new ZipArchive;

        if ($zip->open($absolutePath) !== true) {
            throw new RuntimeException('Could not open .docx file for text extraction.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('.docx file has no word/document.xml — not a valid Word document.');
        }

        // Word inserts run/paragraph boundaries as XML tags with no whitespace
        // between them — replace the paragraph-end tag with a newline before
        // stripping tags, otherwise words from adjacent paragraphs would run
        // together.
        $withBreaks = str_replace('</w:p>', "\n", $xml);
        $text = strip_tags($withBreaks);

        return trim(html_entity_decode($text));
    }
}
