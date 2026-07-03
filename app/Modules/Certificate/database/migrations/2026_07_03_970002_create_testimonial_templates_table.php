<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mirrors transfer_certificate_templates exactly (Student module) — same
        // placeholder-driven HTML template pattern, per-school, one default.
        Schema::create('testimonial_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name')->default('Default Testimonial Template');
            $table->longText('template_body')
                ->comment('HTML with placeholders: {{student_name}}, {{admission_number}}, {{class}}, {{conduct_remark}}, {{grade}}, {{gpa}}, {{percentage}}, {{attendance_percentage}}, {{issued_date}}, {{school_name}}');
            $table->text('footer_text')->nullable();
            $table->string('signatory_name')->nullable();
            $table->string('signatory_designation')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['school_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonial_templates');
    }
};
