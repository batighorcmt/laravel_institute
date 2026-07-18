<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_public_exams', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('school_id')->constrained('groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_public_exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
        });
    }
};
