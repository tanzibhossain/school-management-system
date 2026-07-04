<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_layouts', function (Blueprint $table): void {
            $table->id();
            // Not in the DevPlan's compressed schema line, but CLAUDE.md requires
            // school_id on every non-platform table — added for consistency.
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            // Opaque to Laravel — the Next.js/Craft.js editor owns everything inside
            // this tree. Every save is a NEW row (versioned), never an update, so
            // revision history is just "every row ever created for this page".
            $table->longText('layout_json');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // created_at only — a revision is immutable once written.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['page_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_layouts');
    }
};
