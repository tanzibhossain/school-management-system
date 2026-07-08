<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A fee item with transport_route_id set is a route's transport charge, billed
     * only to students with an active assignment to that route (InvoiceService).
     * NULL = a normal class/tuition fee. Loose link (no FK), same convention as the
     * table's existing cross-module academic_year_id / class_id columns.
     */
    public function up(): void
    {
        Schema::table('fee_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('transport_route_id')->nullable()->after('class_id');
            $table->index(['school_id', 'transport_route_id'], 'fi_school_transport_route_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_items', function (Blueprint $table): void {
            $table->dropIndex('fi_school_transport_route_idx');
            $table->dropColumn('transport_route_id');
        });
    }
};
