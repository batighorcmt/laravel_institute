<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropUnique('uniq_roll_per_scope_new');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->unique(['school_id', 'academic_year_id', 'class_id', 'section_id', 'group_id', 'roll_no'], 'uniq_roll_per_scope_new');
        });
    }
};
