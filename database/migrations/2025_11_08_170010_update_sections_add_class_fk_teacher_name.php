<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (!Schema::hasColumn('sections','class_id')) {
                $table->foreignId('class_id')->nullable()->after('school_id')->constrained('classes')->onDelete('cascade');
            }
            if (!Schema::hasColumn('sections','class_teacher_name')) {
                $table->string('class_teacher_name')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            if (Schema::hasColumn('sections','class_teacher_name')) {
                $table->dropColumn('class_teacher_name');
            }
            if (Schema::hasColumn('sections','class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
        });
    }
};
