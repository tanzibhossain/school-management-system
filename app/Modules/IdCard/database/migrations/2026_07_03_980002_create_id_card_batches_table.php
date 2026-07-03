<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_card_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('type', ['student', 'staff']);
            // nullable + nullOnDelete (matches Testimonial.template_id) — a batch is a
            // generated-artifact record; deleting the template later shouldn't destroy
            // already-rendered batch/PDF history.
            $table->foreignId('template_id')->nullable()->constrained('id_card_templates')->nullOnDelete();
            $table->enum('scope', ['single', 'class', 'all']);
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->json('target_ids')->nullable()->comment('Explicit student/staff IDs when scope=single');
            $table->unsignedInteger('total_count')->default(0);
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->text('error_message')->nullable();
            // nullable + nullOnDelete (not cascade) — matches AdmitCard.generated_by /
            // Testimonial.issued_by: a deleted user account shouldn't destroy batch history.
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_batches');
    }
};
