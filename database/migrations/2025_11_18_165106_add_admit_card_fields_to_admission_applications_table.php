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
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->unsignedInteger('admission_roll_no')->nullable()->after('status');
            $table->dateTime('exam_datetime')->nullable()->after('admission_roll_no');

            $table->unique(['school_id', 'academic_year_id', 'admission_roll_no'], 'admissions_school_year_roll_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            $table->dropUnique('admissions_school_year_roll_unique');
            $table->dropColumn(['admission_roll_no', 'exam_datetime']);
        });
    }
};
