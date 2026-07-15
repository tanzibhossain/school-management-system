<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            // Dedupe keys — indexed so duplicate checks stay cheap. National ID /
            // birth-cert semantics are country-specific; treated as opaque strings.
            $table->string('birth_certificate_no')->nullable()->after('blood_group');
            $table->string('student_phone', 30)->nullable()->after('birth_certificate_no');
            $table->string('father_nid')->nullable()->after('student_phone');
            $table->string('guardian_nid')->nullable()->after('father_nid');

            // Everything else (names, religion, GPA, previous school, parents,
            // address, photo, documents) lives here so schools can enable/disable
            // fields without schema changes and the form stays global-friendly.
            $table->json('form_data')->nullable()->after('notes');

            $table->index(['school_id', 'birth_certificate_no']);
            $table->index(['school_id', 'father_nid']);
            $table->index(['school_id', 'guardian_phone']);
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table): void {
            $table->dropColumn(['birth_certificate_no', 'student_phone', 'father_nid', 'guardian_nid', 'form_data']);
        });
    }
};
