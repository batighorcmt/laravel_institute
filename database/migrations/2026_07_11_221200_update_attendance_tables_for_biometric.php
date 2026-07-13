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
        // Add medium column to student attendance
        Schema::table('attendance', function (Blueprint $table) {
            $table->enum('medium', ['biometric', 'mobile_app', 'web', 'system'])->default('web')->after('status');
        });

        // Convert entry_time and exit_time to time type for student attendance (by modifying the existing datetime columns to time)
        // Note: Using raw queries because changing column types requires doctrine/dbal which might not be installed.
        DB::statement('ALTER TABLE attendance MODIFY entry_time TIME NULL');
        DB::statement('ALTER TABLE attendance MODIFY exit_time TIME NULL');

        // Add medium column to teacher attendance
        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->enum('medium', ['biometric', 'mobile_app', 'web', 'system'])->default('web')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropColumn('medium');
        });
        
        DB::statement('ALTER TABLE attendance MODIFY entry_time DATETIME NULL');
        DB::statement('ALTER TABLE attendance MODIFY exit_time DATETIME NULL');

        Schema::table('teacher_attendances', function (Blueprint $table) {
            $table->dropColumn('medium');
        });
    }
};
