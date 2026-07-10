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
        // For MySQL/MariaDB, we can just use DB::statement to alter the enum
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE biometric_profiles MODIFY COLUMN user_type ENUM('student', 'teacher', 'unassigned') NOT NULL DEFAULT 'unassigned'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this might cause data loss if there are 'unassigned' rows, so we leave it as is or revert carefully
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE biometric_profiles MODIFY COLUMN user_type ENUM('student', 'teacher') NOT NULL");
    }
};
