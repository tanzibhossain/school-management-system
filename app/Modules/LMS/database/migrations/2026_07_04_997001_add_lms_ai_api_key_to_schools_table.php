<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-school Anthropic API key for the assignment AI checker (DevPlan:
     * "Head Teacher enters AI API key in School Settings. Stored encrypted.").
     * Same "extend the shared schools table from within the owning module's
     * own migration" precedent as Sms's sms_cost_per_segment column.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->text('lms_ai_api_key')->nullable()->after('sms_cost_per_segment')
                ->comment('Encrypted Anthropic API key used by AssignmentAiCheckJob; null = AI checking disabled for this school');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('lms_ai_api_key');
        });
    }
};
