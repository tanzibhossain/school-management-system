<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            // institution_code_label already exists (Field 1). Add labels for the
            // other two configurable code fields so each is a generic label/value pair.
            $table->string('school_code_label', 50)->nullable()->after('school_code');
            $table->string('technical_branch_code_label', 50)->nullable()->after('technical_branch_code');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['school_code_label', 'technical_branch_code_label']);
        });
    }
};
