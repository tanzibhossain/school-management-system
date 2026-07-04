<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform-level (no school_id — the school doesn't exist yet). Only used for
        // the PAID self-serve path: a visitor starts a Stripe Checkout session before
        // the school/admin are created, and the actual provisioning happens once the
        // webhook confirms payment — this row survives that round-trip. Trial signups
        // (free, no payment) skip this table entirely and provision immediately.
        Schema::create('pending_school_signups', function (Blueprint $table): void {
            $table->id();
            $table->string('school_name');
            $table->string('desired_subdomain');
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('admin_name');
            $table->string('admin_email');
            $table->char('country_code', 2)->nullable();
            $table->string('stripe_checkout_session_id')->nullable()->unique();
            $table->enum('status', ['pending', 'completed', 'failed', 'expired'])->default('pending');
            $table->foreignId('created_school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->timestamps();

            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_school_signups');
    }
};
