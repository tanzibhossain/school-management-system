<?php

namespace Tests\Unit\LMS;

use App\Modules\LMS\Services\SubmissionContentExtractor;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class SubmissionContentExtractorTest extends TestCase
{
    private SubmissionContentExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new SubmissionContentExtractor();
    }

    public function test_extracts_plain_text_verbatim(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lms_test_');
        file_put_contents($path, 'Hello from a plain text submission.');

        $this->assertSame('Hello from a plain text submission.', $this->extractor->extract($path, 'txt'));

        unlink($path);
    }

    public function test_extracts_text_from_a_docx_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lms_test_') . '.docx';

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString(
            'word/document.xml',
            '<?xml version="1.0"?><w:document xmlns:w="ns"><w:body>'
            . '<w:p><w:r><w:t>First paragraph.</w:t></w:r></w:p>'
            . '<w:p><w:r><w:t>Second paragraph.</w:t></w:r></w:p>'
            . '</w:body></w:document>'
        );
        $zip->close();

        $text = $this->extractor->extract($path, 'docx');

        $this->assertStringContainsString('First paragraph.', $text);
        $this->assertStringContainsString('Second paragraph.', $text);

        unlink($path);
    }

    public function test_pdf_is_a_documented_unsupported_gap(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF text extraction is not supported');

        $this->extractor->extract('/tmp/whatever.pdf', 'pdf');
    }
}
