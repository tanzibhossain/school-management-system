<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('sms_batches')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('guardian_id')->nullable()->constrained('student_guardians')->nullOnDelete();
            // Denormalized — history survives even if the guardian's phone is edited later,
            // and null (not skipped) is the recorded reason a send failed with no phone on file.
            $table->string('recipient_phone')->nullable();
            $table->text('body');
            $table->enum('encoding', ['gsm7', 'unicode']);
            $table->unsignedSmallInteger('segment_count');
            $table->decimal('cost', 10, 4)->nullable()->comment('null when school.sms_cost_per_segment is not configured');
            $table->enum('status', ['sent', 'failed']);
            $table->string('error_message')->nullable();
            $table->enum('purpose', ['manual', 'due_reminder']);
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            // Self-referencing — a resend creates a NEW row pointing back at the original
            // failed attempt rather than mutating it, preserving full send history.
            $table->foreignId('resent_from_id')->nullable()->constrained('sms_logs')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'student_id']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
