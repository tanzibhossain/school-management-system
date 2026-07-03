<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('type', ['student', 'staff']);
            $table->string('original_filename');
            // Where the uploaded spreadsheet was stashed (MinIO) so the queued job can
            // read it back after the request ends — same reasoning as IdCard's rendered
            // PDFs and Sms's per-recipient logs living outside the request lifecycle.
            $table->string('stored_path')->nullable();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            // Array of {row: int, messages: string[]} — one entry per skipped row.
            // A JSON column rather than a child table: unlike Sms's per-recipient logs,
            // an import error isn't individually actionable (no per-row "resend") — it's
            // read-only report output, bounded by the file's row count.
            $table->json('errors')->nullable();
            // Whole-file failure only (e.g. unreadable file) — distinct from per-row errors.
            $table->text('error_message')->nullable();
            // nullable + nullOnDelete — matches every other batch/request module
            // (IdCard, Sms, Leave, Loan): a deleted user shouldn't destroy import history.
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
