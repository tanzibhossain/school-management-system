<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_academics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years');
            $table->foreignId('class_id')->constrained('classes');
            $table->foreignId('section_id')->constrained('sections');
            $table->foreignId('version_id')->nullable()->constrained('academic_versions')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('academic_groups')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('academic_shifts')->nullOnDelete();
            $table->string('roll_number', 20)->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamp('promoted_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'is_current']);
            $table->index(['school_id', 'class_id', 'section_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_academics');
    }
};
