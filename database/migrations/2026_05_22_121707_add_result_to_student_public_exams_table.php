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
        Schema::table('student_public_exams', function (Blueprint $table) {
            $table->string('result')->nullable()->after('center_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_public_exams', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
