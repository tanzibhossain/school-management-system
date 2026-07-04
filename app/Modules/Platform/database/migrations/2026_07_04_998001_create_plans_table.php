<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform-level table — deliberately NO school_id (same exception CLAUDE.md
        // already carves out for `schools` itself). A plan exists independently of any
        // one school; many schools reference the same plan row.
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Nullable — Demo/Trial are free and have no price at all.
            $table->decimal('price_monthly', 10, 2)->nullable();
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            // Null = unlimited (Pro).
            $table->unsignedInteger('max_students')->nullable();
            $table->unsignedInteger('max_staff')->nullable();
            // Only meaningful for the Trial plan.
            $table->unsignedInteger('trial_days')->nullable();
            // Whether a visitor can self-serve purchase/activate this plan via the
            // public signup/checkout endpoints. Demo is never self-serve (there is only
            // ever the one shared demo school, provisioned once via seeder).
            $table->boolean('is_self_serve')->default(false);
            // Super Admin can retire a plan from new signups without breaking schools
            // already on it.
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
