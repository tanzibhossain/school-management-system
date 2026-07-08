<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen BOTH sms purpose enums to include 'transport_alert' so Transport-module
     * vehicle-swap notifications are distinguishable in SMS history/billing. Both
     * sms_batches.purpose and sms_logs.purpose carry the enum, so both must move in
     * lockstep or the per-recipient SmsLog insert fails its CHECK constraint.
     * Native ->change() (Laravel 13) rebuilds the enum/check on MySQL and SQLite.
     */
    public function up(): void
    {
        Schema::table('sms_batches', function (Blueprint $table): void {
            $table->enum('purpose', ['manual', 'due_reminder', 'transport_alert'])->change();
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->enum('purpose', ['manual', 'due_reminder', 'transport_alert'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('sms_batches', function (Blueprint $table): void {
            $table->enum('purpose', ['manual', 'due_reminder'])->change();
        });

        Schema::table('sms_logs', function (Blueprint $table): void {
            $table->enum('purpose', ['manual', 'due_reminder'])->change();
        });
    }
};
