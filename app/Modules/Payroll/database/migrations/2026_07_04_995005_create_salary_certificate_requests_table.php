<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_certificate_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('purpose');
            $table->string('status', 10)->default('pending'); // pending | generated
            $table->string('certificate_path')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'staff_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_certificate_requests');
    }
};
