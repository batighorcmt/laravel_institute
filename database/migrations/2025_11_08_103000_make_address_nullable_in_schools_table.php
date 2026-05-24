<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make address column nullable (was NOT NULL causing insert failure when left blank).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Using raw SQL to avoid requiring doctrine/dbal for change()
        DB::statement('ALTER TABLE schools MODIFY address VARCHAR(255) NULL');
    }

    /**
     * Revert address column back to NOT NULL.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // If rows exist with NULL address this will fail; ensure cleanup before rollback.
        DB::statement("UPDATE schools SET address = '' WHERE address IS NULL");
        DB::statement('ALTER TABLE schools MODIFY address VARCHAR(255) NOT NULL');
    }
};
