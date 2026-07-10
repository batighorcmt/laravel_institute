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
        // For Students: set biometric_id = student_id where biometric_id is null
        DB::statement('UPDATE students SET biometric_id = student_id WHERE biometric_id IS NULL');

        // For Teachers: set biometric_id = 900000 + id (or similar logic)
        DB::statement('UPDATE teachers SET biometric_id = CAST(900000 + id AS CHAR) WHERE biometric_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed, data retention
    }
};
