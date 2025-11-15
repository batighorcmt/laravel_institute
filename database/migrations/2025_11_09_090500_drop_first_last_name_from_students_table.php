<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Backfill student_name_en from first_name + last_name if needed
        if (Schema::hasColumn('students','first_name') && Schema::hasColumn('students','last_name') && Schema::hasColumn('students','student_name_en')) {
            DB::table('students')
                ->whereNull('student_name_en')
                ->update(['student_name_en' => DB::raw("TRIM(CONCAT(IFNULL(first_name,''),' ',IFNULL(last_name,'')))")]);
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students','first_name')) {
                $table->dropColumn('first_name');
            }
            if (Schema::hasColumn('students','last_name')) {
                $table->dropColumn('last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students','first_name')) {
                $table->string('first_name')->nullable()->after('student_id');
            }
            if (!Schema::hasColumn('students','last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });
    }
};
