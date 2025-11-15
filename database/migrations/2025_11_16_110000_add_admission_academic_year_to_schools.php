<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools','admission_academic_year_id')) {
                $table->unsignedBigInteger('admission_academic_year_id')->nullable()->after('admissions_enabled');
                $table->foreign('admission_academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools','admission_academic_year_id')) {
                $table->dropForeign(['admission_academic_year_id']);
                $table->dropColumn('admission_academic_year_id');
            }
        });
    }
};
