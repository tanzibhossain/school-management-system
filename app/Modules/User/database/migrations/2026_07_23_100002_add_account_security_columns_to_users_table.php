<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns for the self-service account page: TOTP two-factor auth (secret +
 * recovery codes are stored via User's `encrypted`/`encrypted:array` casts, so
 * they're at rest-encrypted with APP_KEY — never store these plain) and a
 * pending-email-change flow (new address isn't applied until its confirmation
 * link is clicked; the token is hashed the same way password-reset tokens are).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            $table->string('pending_email')->nullable()->after('email');
            $table->string('pending_email_token')->nullable()->after('pending_email');
            $table->timestamp('pending_email_expires_at')->nullable()->after('pending_email_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'pending_email',
                'pending_email_token',
                'pending_email_expires_at',
            ]);
        });
    }
};
