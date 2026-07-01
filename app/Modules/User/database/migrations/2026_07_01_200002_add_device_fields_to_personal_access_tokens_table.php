<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: Sanctum migration must have run first
        if (! Schema::hasTable('personal_access_tokens')) {
            throw new \RuntimeException(
                'personal_access_tokens table missing. Run: php artisan vendor:publish --tag="sanctum-migrations" then migrate again.'
            );
        }

        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            if (! Schema::hasColumn('personal_access_tokens', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('name');
            }
            if (! Schema::hasColumn('personal_access_tokens', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
