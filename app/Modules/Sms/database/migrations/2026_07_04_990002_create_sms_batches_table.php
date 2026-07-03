<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('purpose', ['manual', 'due_reminder']);
            // Same targeting shape as IdCard's batches — scope=single uses target_ids,
            // scope=class uses class_id (+ optional section_id), scope=all is everyone.
            $table->enum('scope', ['single', 'class', 'all']);
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->json('target_ids')->nullable()->comment('Explicit student IDs when scope=single');
            // Only used for purpose=manual — the exact text sent to every recipient
            // verbatim (no per-school template system was scoped for this pass). For
            // purpose=due_reminder the message is computed per-recipient in the job
            // (needs each student's own amount/due date), so this stays null there.
            $table->text('message_body')->nullable();
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->unsignedInteger('total_count')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_batches');
    }
};
