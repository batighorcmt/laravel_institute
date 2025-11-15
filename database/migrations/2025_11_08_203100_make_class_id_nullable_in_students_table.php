<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Make class_id nullable if not already
        if (Schema::hasColumn('students','class_id')) {
            // Use raw SQL to avoid requiring doctrine/dbal
            $connection = config('database.default');
            $driver = config("database.connections.$connection.driver");
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `students` MODIFY `class_id` BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        // Revert to NOT NULL only if column exists
        if (Schema::hasColumn('students','class_id')) {
            $connection = config('database.default');
            $driver = config("database.connections.$connection.driver");
            if ($driver === 'mysql') {
                // This assumes all rows have a class_id; otherwise it will fail
                DB::statement('ALTER TABLE `students` MODIFY `class_id` BIGINT UNSIGNED NOT NULL');
            }
        }
    }
};
