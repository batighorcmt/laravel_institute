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
        Schema::table('school_attendance_settings', function (Blueprint $table) {
            $table->boolean('auto_attendance_enabled')->default(true)->after('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_attendance_settings', function (Blueprint $table) {
            $table->dropColumn('auto_attendance_enabled');
        });
    }
};
