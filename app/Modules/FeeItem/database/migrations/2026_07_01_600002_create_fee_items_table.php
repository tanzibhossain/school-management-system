<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->foreignId('category_id')->constrained('fee_categories')->cascadeOnDelete();
            $table->unsignedBigInteger('academic_year_id');         // cross-module — no FK
            $table->unsignedBigInteger('class_id')->nullable();     // cross-module; null = all classes
            $table->string('name', 150);
            $table->decimal('amount', 10, 2);
            $table->enum('frequency', ['monthly', 'quarterly', 'yearly', 'one_time'])->default('monthly');
            $table->unsignedTinyInteger('due_day')->nullable();     // 1–28, day of month
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'class_id'], 'fi_school_year_class_idx');
            $table->index(['school_id', 'is_active'], 'fi_school_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_items');
    }
};
