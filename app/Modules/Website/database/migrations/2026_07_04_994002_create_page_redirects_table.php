<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_redirects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('old_slug');
            $table->string('new_slug');
            // created_at only — no updated_at, matches the DevPlan's schema exactly.
            // A redirect row is an immutable log entry, never edited after creation.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'old_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_redirects');
    }
};
