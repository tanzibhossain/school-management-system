<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Generic enable/disable gate for the optional modules (20-24 in CLAUDE.md's
     * build order: Payroll, LMS, Library, Transport, Messaging). Deliberately
     * deferred during Payroll's build ("better designed once, for all five") —
     * built now, on LMS, and retrofitted onto Payroll's routes in the same pass.
     * Absence of a row for a given school+module means disabled (default false),
     * matching the DevPlan's "Head Teacher enables it" opt-in framing exactly.
     */
    public function up(): void
    {
        Schema::create('school_module_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->enum('module', ['payroll', 'lms', 'library', 'transport', 'messaging']);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['school_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_module_settings');
    }
};
