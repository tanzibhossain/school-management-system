<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()
                ->constrained('testimonial_templates')->nullOnDelete();
            // The exam whose result is featured as the "academic summary" — nullable,
            // a testimonial can be a pure conduct reference with no result attached.
            $table->foreignId('exam_id')->nullable()->constrained('exams')->nullOnDelete();
            $table->string('testimonial_number', 50)->comment('e.g. TST/2026/001');
            $table->date('issued_date');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('conduct_remark');
            // Optional attendance window — explicit, not inferred from academic_years
            // (which has no start/end dates; inferring calendar bounds would bake in
            // a BD-style assumption the Global Product Rules explicitly warn against).
            $table->date('attendance_from')->nullable();
            $table->date('attendance_to')->nullable();
            $table->string('file_path')->nullable()->comment('Generated PDF path in MinIO');
            $table->enum('status', ['draft', 'issued'])->default('draft');
            $table->timestamps();

            $table->unique(['school_id', 'testimonial_number']);
            $table->index(['school_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
