<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Session-based (Blade admin/staff/portal) logins don't issue a Sanctum token
 * like the API flow does, so there's nothing to key a "device" off. Storing the
 * actual session ID on the history row lets the account page list live sessions
 * and revoke a specific one by destroying that exact session from the store.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_histories', function (Blueprint $table): void {
            $table->string('session_id')->nullable()->after('device_name');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('login_histories', function (Blueprint $table): void {
            $table->dropIndex(['session_id']);
            $table->dropColumn('session_id');
        });
    }
};
