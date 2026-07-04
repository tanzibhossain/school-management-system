<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_layouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('type', ['header', 'footer']);
            // Same versioning pattern as page_layouts, keyed by type instead of page_id —
            // only two "current" chains per school (one header, one footer).
            $table->longText('layout_json');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'type', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_layouts');
    }
};
