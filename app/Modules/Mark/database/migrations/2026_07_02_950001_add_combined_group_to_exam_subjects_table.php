<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Combined subjects (e.g. Bangla 1st + 2nd paper): exam subjects sharing a
     * combined_group value within one exam are graded as ONE subject — marks
     * summed, pass judged on the combined total, one grade for the group.
     */
    public function up(): void
    {
        Schema::table('exam_subjects', function (Blueprint $table): void {
            $table->unsignedSmallInteger('combined_group')->nullable()->after('pass_marks');
        });
    }

    public function down(): void
    {
        Schema::table('exam_subjects', function (Blueprint $table): void {
            $table->dropColumn('combined_group');
        });
    }
};
