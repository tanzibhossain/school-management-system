<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('applies_to', 10)->default('both'); // student | staff | both
            $table->unsignedInteger('max_days_per_year')->nullable(); // null = unlimited
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_paid')->nullable(); // staff-relevant only; meaningless for students
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
