<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students','student_name_bn')) {
                $table->string('student_name_bn')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('students','father_name_bn')) {
                $table->string('father_name_bn')->nullable()->after('father_name');
            }
            if (!Schema::hasColumn('students','mother_name_bn')) {
                $table->string('mother_name_bn')->nullable()->after('mother_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students','student_name_bn')) {
                $table->dropColumn('student_name_bn');
            }
            if (Schema::hasColumn('students','father_name_bn')) {
                $table->dropColumn('father_name_bn');
            }
            if (Schema::hasColumn('students','mother_name_bn')) {
                $table->dropColumn('mother_name_bn');
            }
        });
    }
};
