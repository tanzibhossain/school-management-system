<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per rendered PDF chunk (max 200 cards each — see GenerateIdCardBatchJob).
        // A small batch produces exactly one row; a large "all students" batch produces several.
        Schema::create('id_card_batch_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('batch_id')->constrained('id_card_batches')->cascadeOnDelete();
            $table->unsignedInteger('file_index');
            $table->string('file_path');
            $table->unsignedInteger('card_count');
            $table->timestamps();

            $table->unique(['batch_id', 'file_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_batch_files');
    }
};
