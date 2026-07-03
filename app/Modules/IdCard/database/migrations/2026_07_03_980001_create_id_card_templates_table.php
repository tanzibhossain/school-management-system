<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_card_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('type', ['student', 'staff']);
            $table->string('name');
            // Only horizontal_classic and vertical are rendered today (see IdCardRenderer);
            // the other 3 are valid config values a school can pick ahead of those templates
            // being built, so no migration is needed when they land.
            $table->enum('layout', ['horizontal_classic', 'horizontal_modern', 'vertical', 'dual_stripe', 'minimal'])
                ->default('horizontal_classic');
            $table->string('background_color', 20)->default('#ffffff');
            $table->string('accent_color', 20)->default('#1a56db');
            $table->string('logo_path')->nullable()->comment('MinIO object path');
            $table->enum('font', ['sans', 'serif', 'mono'])->default('sans');
            // Nullable rather than defaulted — a template can be created before deciding
            // which extra fields to show; IdCardRenderer treats a null/missing list as "none".
            $table->json('visible_fields')->nullable()->comment('Which of name/id/class/section/photo/blood_group/etc. to render');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_card_templates');
    }
};
