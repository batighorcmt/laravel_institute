<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make migration idempotent: only add columns/index if they don't exist.
        if (!Schema::hasColumn('admission_applications', 'admission_roll_no') || !Schema::hasColumn('admission_applications', 'exam_datetime')) {
            Schema::table('admission_applications', function (Blueprint $table) {
                if (!Schema::hasColumn('admission_applications', 'admission_roll_no')) {
                    $table->unsignedInteger('admission_roll_no')->nullable()->after('status');
                }
                if (!Schema::hasColumn('admission_applications', 'exam_datetime')) {
                    $table->dateTime('exam_datetime')->nullable()->after('admission_roll_no');
                }
            });
        }

        // Ensure unique index exists (silently ignore if creation fails)
        try {
            Schema::table('admission_applications', function (Blueprint $table) {
                $table->unique(['school_id', 'academic_year_id', 'admission_roll_no'], 'admissions_school_year_roll_unique');
            });
        } catch (\Throwable $e) {
            // index likely exists or columns missing; ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications','admission_roll_no')) {
                try { $table->dropUnique('admissions_school_year_roll_unique'); } catch (\Throwable $e) {}
                $table->dropColumn(['admission_roll_no']);
            }
            if (Schema::hasColumn('admission_applications','exam_datetime')) {
                $table->dropColumn(['exam_datetime']);
            }
        });
    }
};
