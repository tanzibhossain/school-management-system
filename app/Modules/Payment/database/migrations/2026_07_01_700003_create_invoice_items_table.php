<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('fee_item_id');          // cross-module — no FK
            $table->string('name', 150);                        // denormalized at invoice time
            $table->decimal('amount', 10, 2);                   // fee amount at invoice time
            $table->unsignedBigInteger('discount_id')->nullable(); // cross-module — no FK
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);               // amount − discount_amount
            $table->timestamps();

            $table->index('invoice_id', 'inv_item_invoice_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
