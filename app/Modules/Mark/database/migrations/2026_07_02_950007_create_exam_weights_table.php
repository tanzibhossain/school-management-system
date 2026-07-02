<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Year-end combined result config: e.g. Half-Yearly 30% + Annual 70%.
     * Configurable per school/class/year; weights should sum to 100.
     */
    public function up(): void
    {
        Schema::create('exam_weights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->decimal('weight_percent', 5, 2);
            $table->timestamps();

            $table->unique(['class_id', 'academic_year_id', 'exam_id'], 'exam_weight_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_weights');
    }
};
