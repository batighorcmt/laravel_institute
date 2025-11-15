<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_applications','academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('school_id');
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
                $table->index('academic_year_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_applications', function (Blueprint $table) {
            if (Schema::hasColumn('admission_applications','academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropIndex(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }
        });
    }
};
