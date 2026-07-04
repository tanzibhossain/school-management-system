<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedTinyInteger('month'); // 1-12
            $table->unsignedSmallInteger('year');
            $table->string('status', 10)->default('draft'); // draft | approved | paid
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'month', 'year']);
            $table->index(['school_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
