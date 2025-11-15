<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make address column nullable (was NOT NULL causing insert failure when left blank).
     */
    public function up(): void
    {
        // Using raw SQL to avoid requiring doctrine/dbal for change()
        DB::statement('ALTER TABLE schools MODIFY address VARCHAR(255) NULL');
    }

    /**
     * Revert address column back to NOT NULL.
     */
    public function down(): void
    {
        // If rows exist with NULL address this will fail; ensure cleanup before rollback.
        DB::statement("UPDATE schools SET address = '' WHERE address IS NULL");
        DB::statement('ALTER TABLE schools MODIFY address VARCHAR(255) NOT NULL');
    }
};
