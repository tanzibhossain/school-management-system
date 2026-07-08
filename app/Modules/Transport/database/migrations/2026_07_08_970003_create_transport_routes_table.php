<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            // Canonical fare — mirrored into the linked FeeItem and synced down to
            // Academic's transports.fee for public display.
            $table->decimal('fare', 10, 2)->default(0);
            // Loose cross-module links (no FK, same convention as fee_items' year/class).
            $table->unsignedBigInteger('fee_item_id')->nullable();
            $table->unsignedBigInteger('academic_transport_id')->nullable();
            // Within-module links.
            $table->foreignId('current_vehicle_id')->nullable()->constrained('transport_vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('transport_drivers')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'is_active'], 'tr_school_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_routes');
    }
};
