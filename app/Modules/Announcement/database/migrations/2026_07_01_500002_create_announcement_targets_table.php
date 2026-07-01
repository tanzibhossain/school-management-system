<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->enum('target_type', ['class', 'section']);
            // Cross-module refs — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('target_id')->comment('class_id or section_id');
            $table->timestamps();

            $table->unique(['announcement_id', 'target_type', 'target_id'], 'ann_target_unique_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_targets');
    }
};
