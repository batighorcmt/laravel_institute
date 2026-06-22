<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix the seat_plans.id column missing AUTO_INCREMENT.
     * This happens when the table was created by an earlier migration
     * that did not use $table->id() properly.
     */
    public function up(): void
    {
        // Only fix if AUTO_INCREMENT is missing
        $columns = DB::select("SHOW COLUMNS FROM `seat_plans` WHERE Field = 'id'");

        if (!empty($columns) && strpos($columns[0]->Extra, 'auto_increment') === false) {
            DB::statement('ALTER TABLE `seat_plans` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        // Reverting AUTO_INCREMENT is destructive — left intentionally empty.
    }
};
