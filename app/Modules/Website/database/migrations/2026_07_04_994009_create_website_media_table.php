<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_media', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('filename');
            // MinIO path — named "path" rather than the DevPlan's literal "r2_path"
            // to match this codebase's actual storage convention (MinIO, not R2).
            $table->string('path');
            $table->string('mime_type');
            $table->string('alt_text')->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width_px')->nullable();
            $table->unsignedInteger('height_px')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_media');
    }
};
