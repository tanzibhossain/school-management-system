<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            // Class teacher = the staff member responsible for this section's daily attendance
            $table->foreignId('class_teacher_id')->nullable()->after('name')
                ->constrained('staff')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('class_teacher_id');
        });
    }
};
