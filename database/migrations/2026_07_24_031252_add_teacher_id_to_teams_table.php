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
        Schema::table('teams', function (Blueprint $table) {
            // References users.id (same pattern as extra_classes.teacher_id) —
            // this is the actual authorization link that lets a team's own
            // teacher take its attendance; instructor_name stays as a
            // free-text display label only.
            $table->foreignId('teacher_id')->nullable()->after('instructor_name')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
