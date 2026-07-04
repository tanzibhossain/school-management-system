<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_salary_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained('salary_components')->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['staff_id', 'salary_component_id']);
            $table->index(['school_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_salary_values');
    }
};
