<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Per-school SMS billing (per the confirmed decision — v2 does NOT follow the
        // DevPlan's "platform-level single account" note; School already had
        // sms_api_key/sms_sender_id sitting unused, so this module completes that
        // per-school credential set with the rate needed for cost calculation).
        Schema::table('schools', function (Blueprint $table): void {
            $table->decimal('sms_cost_per_segment', 8, 4)->nullable()->after('sms_sender_id')
                ->comment('Cost per SMS segment in the school currency; null = cost not tracked, only segments');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('sms_cost_per_segment');
        });
    }
};
