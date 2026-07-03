<?php

namespace App\Modules\IdCard\Jobs;

use App\Modules\IdCard\Models\IdCardBatch;
use App\Modules\IdCard\Models\IdCardBatchFile;
use App\Modules\IdCard\Services\IdCardBatchService;
use App\Modules\IdCard\Services\IdCardRenderer;
use App\Modules\School\Models\School;
use App\Services\PdfRenderingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * The codebase's first queued job (every other module's PDF work — Admit
 * Card, Testimonial, Transfer Certificate — renders synchronously in the
 * request). ID card batches can cover an entire school, so this runs on
 * Horizon instead. QUEUE_CONNECTION=sync in tests, so it still executes
 * inline there — no fake queue plumbing needed to test it.
 *
 * Splits the target set into chunks of 200 cards; each chunk becomes its own
 * PDF (id_card_batch_files row), so a 900-student "all students" batch
 * produces 5 files rather than one unbounded PDF.
 */
class GenerateIdCardBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHUNK_SIZE = 200;

    public function __construct(private readonly int $batchId) {}

    public function handle(IdCardBatchService $service, IdCardRenderer $renderer, PdfRenderingService $pdf): void
    {
        $batch = IdCardBatch::findOrFail($this->batchId);
        $batch->update(['status' => 'processing']);

        try {
            $school = School::findOrFail($batch->school_id);
            $template = $batch->template;

            $query = $service->targetQuery($batch->school_id, $batch->type, $batch->scope, [
                'class_id' => $batch->class_id,
                'section_id' => $batch->section_id,
                'target_ids' => $batch->target_ids,
            ])->orderBy('name');

            $records = $batch->type === 'student'
                ? $query->with(['currentAcademic.schoolClass', 'currentAcademic.section', 'currentAcademic.year'])->get()
                : $query->with(['designation', 'department'])->get();

            // Resolved once — same logo file and phone number for every card in the batch.
            $logoDataUri = $service->resolveDataUri($template->logo_path);
            $phone = $service->schoolPhone($school);

            $fileIndex = 0;
            foreach ($records->chunk(self::CHUNK_SIZE) as $chunk) {
                $fileIndex++;

                $cardHtmls = $chunk
                    ->map(fn ($record) => $renderer->render($template, $service->cardData($batch->type, $record, $school, $logoDataUri, $phone)))
                    ->all();

                $sheetHtml = $renderer->wrapSheet($cardHtmls, $template);

                $path = $pdf->generateAndStore(
                    $sheetHtml,
                    "id-cards/{$batch->school_id}/batches/{$batch->id}/{$fileIndex}.pdf",
                );

                IdCardBatchFile::create([
                    'school_id' => $batch->school_id,
                    'batch_id' => $batch->id,
                    'file_index' => $fileIndex,
                    'file_path' => $path,
                    'card_count' => $chunk->count(),
                ]);
            }

            $batch->update(['status' => 'completed', 'generated_at' => now()]);
        } catch (Throwable $e) {
            // Swallowed (not rethrown): the batch row's status/error_message is the
            // client-facing failure signal (per the polling design), and rethrowing
            // would propagate into the HTTP request that dispatched it under the
            // sync queue driver used in tests.
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}
