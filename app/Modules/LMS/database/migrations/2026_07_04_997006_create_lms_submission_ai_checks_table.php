<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_submission_ai_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            // One check per submission — AssignmentAiCheckJob upserts this row
            // rather than appending a history of checks.
            $table->foreignId('submission_id')->unique()->constrained('lms_submissions')->cascadeOnDelete();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->boolean('likely_ai_generated')->nullable();
            $table->text('originality_note')->nullable();
            $table->json('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_submission_ai_checks');
    }
};
