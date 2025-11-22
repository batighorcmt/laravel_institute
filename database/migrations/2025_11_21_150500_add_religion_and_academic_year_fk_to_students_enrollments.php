<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add religion to students if missing
        if (!Schema::hasColumn('students','religion')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('religion', 32)->nullable()->after('gender');
            });
        }
        
        // Add foreign key constraint for academic_year_id (column already exists from create migration)
        $fkExists = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'student_enrollments' 
            AND COLUMN_NAME = 'academic_year_id' AND REFERENCED_TABLE_NAME IS NOT NULL");
        if (empty($fkExists)) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Remove religion column if exists
        if (Schema::hasColumn('students','religion')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('religion');
            });
        }
    }
};
