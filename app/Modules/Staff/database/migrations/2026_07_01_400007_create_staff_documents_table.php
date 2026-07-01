<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->enum('document_type', ['nid', 'passport', 'certificate', 'contract', 'other']);
            $table->string('file_path')->comment('MinIO object path');
            $table->string('original_name', 255);
            // Cross-module ref to users — no DB-level FK
            $table->unsignedBigInteger('uploaded_by')->nullable()->comment('User who uploaded');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
    }
};
