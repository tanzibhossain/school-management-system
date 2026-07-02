<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->char('currency', 3)->default('USD')->after('amount_due');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->char('currency', 3)->default('USD')->after('amount');
        });

        // Backfill from the owning school's currency (portable subquery — works on MySQL + SQLite)
        DB::statement("
            UPDATE invoices
            SET currency = COALESCE((SELECT currency FROM schools WHERE schools.id = invoices.school_id), 'USD')
        ");
        DB::statement("
            UPDATE payments
            SET currency = COALESCE((SELECT currency FROM schools WHERE schools.id = payments.school_id), 'USD')
        ");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
