<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `weight` (a manual display-order field) is no longer used — class/subject
 * ordering is by name. Guarded so fresh installs (which never created the column)
 * skip cleanly while existing databases drop it without data loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['classes', 'subjects'] as $table) {
            if (Schema::hasColumn($table, 'weight')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->dropColumn('weight');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['classes', 'subjects'] as $table) {
            if (! Schema::hasColumn($table, 'weight')) {
                Schema::table($table, function (Blueprint $t): void {
                    $t->unsignedTinyInteger('weight')->default(0);
                });
            }
        }
    }
};
