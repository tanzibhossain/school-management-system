<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('license_no', 50)->nullable();
            $table->enum('status', ['active', 'on_leave', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['school_id', 'status'], 'td_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_drivers');
    }
};
