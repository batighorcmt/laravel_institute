<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admission_exams', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_exams','overall_full_mark')) {
                $table->unsignedInteger('overall_full_mark')->nullable()->after('overall_pass_mark');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_exams', function (Blueprint $table) {
            if (Schema::hasColumn('admission_exams','overall_full_mark')) {
                $table->dropColumn('overall_full_mark');
            }
        });
    }
};
