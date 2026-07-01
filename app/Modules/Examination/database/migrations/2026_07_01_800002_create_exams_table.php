<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('exam_type_id')->constrained('exam_types')->cascadeOnDelete();

            // Cross-module refs — no DB-level FK; enforced at application layer
            $table->unsignedBigInteger('academic_year_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id')->nullable();  // null = all sections
            $table->unsignedBigInteger('group_id')->nullable();    // null = all groups
            $table->unsignedBigInteger('version_id')->nullable();  // null = all versions

            $table->string('title', 200);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'published', 'completed'])->default('draft');
            $table->enum('seating_strategy', ['sequential', 'interleave_group', 'interleave_section', 'anti_adjacency'])
                ->default('sequential');
            $table->timestamps();

            $table->index(['school_id', 'class_id', 'academic_year_id'], 'exam_school_class_year_idx');
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
