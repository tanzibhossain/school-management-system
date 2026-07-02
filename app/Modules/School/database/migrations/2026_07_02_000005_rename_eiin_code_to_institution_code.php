<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // "EIIN" is a Bangladesh-only term — the code itself is generic, the label is configurable
            $table->renameColumn('eiin_code', 'institution_code');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->string('institution_code_label', 50)->default('Institution Code')->after('name');
        });

        // Backfill: existing tenants are Bangladesh schools where the code is the EIIN
        DB::table('schools')->update(['institution_code_label' => 'EIIN']);
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('institution_code_label');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->renameColumn('institution_code', 'eiin_code');
        });
    }
};
