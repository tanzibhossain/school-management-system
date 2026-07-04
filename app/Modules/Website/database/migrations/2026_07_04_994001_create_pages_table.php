<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->string('meta_title')->nullable();
            $table->text('meta_desc')->nullable();
            $table->string('og_image')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            // Denormalized convenience flag — kept in sync by PageService::setHomepage().
            // The real source of truth is site_settings.homepage_page_id.
            $table->boolean('is_homepage')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'slug']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
