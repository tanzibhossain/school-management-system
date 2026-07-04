<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lms_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
            $table->string('title');
            $table->enum('content_type', ['text', 'video', 'file']);
            $table->longText('body_text')->nullable();
            $table->string('video_url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'course_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lms_lessons');
    }
};
