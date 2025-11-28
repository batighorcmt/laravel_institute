<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Convert routine_entries.teacher_id from users.id to teachers.id when possible
        // Only updates rows where a matching teacher exists for the same user and school
        DB::statement('UPDATE routine_entries re
            JOIN teachers t ON t.user_id = re.teacher_id AND t.school_id = re.school_id
            SET re.teacher_id = t.id');
    }

    public function down(): void
    {
        // Irreversible without a backup; no-op
    }
};
