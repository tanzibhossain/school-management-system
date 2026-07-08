<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('registration_no', 50);
            $table->unsignedSmallInteger('capacity');
            // available = the pool; in_service = currently serving a route;
            // out_of_service = un-operable (broken / maintenance).
            $table->enum('status', ['available', 'in_service', 'out_of_service'])->default('available');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'registration_no'], 'tv_school_reg_unique');
            $table->index(['school_id', 'status'], 'tv_school_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_vehicles');
    }
};
