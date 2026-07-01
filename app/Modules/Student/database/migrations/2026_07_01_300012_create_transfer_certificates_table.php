<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_certificates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()
                ->constrained('transfer_certificate_templates')->nullOnDelete();
            $table->string('tc_number', 50)->comment('e.g. TC/2026/001');
            $table->date('issued_date');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('reason', ['transfer', 'withdrawal', 'completion']);
            $table->string('file_path')->nullable()->comment('Generated PDF path in MinIO');
            $table->enum('status', ['draft', 'issued'])->default('draft');
            $table->timestamps();

            $table->unique(['school_id', 'tc_number']);
            $table->index(['school_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_certificates');
    }
};
