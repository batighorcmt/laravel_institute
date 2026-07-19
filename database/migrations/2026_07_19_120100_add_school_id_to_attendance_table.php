<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->after('student_id')
                ->constrained('schools')->cascadeOnDelete();
        });

        // Backfill existing rows from the student's school, since attendance
        // previously only recorded school membership indirectly via student_id.
        DB::statement(
            'UPDATE attendance a JOIN students s ON a.student_id = s.id ' .
            'SET a.school_id = s.school_id WHERE a.school_id IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
        });
    }
};
