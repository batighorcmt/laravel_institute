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
        Schema::table('groups', function (Blueprint $table) {
            // First add the new unique constraint. This will serve as the index for school_id foreign key.
            $table->unique(['school_id', 'class_id', 'name']);
            // Now we can safely drop the old one.
            $table->dropUnique(['school_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->unique(['school_id', 'name']);
            $table->dropUnique(['school_id', 'class_id', 'name']);
        });
    }
};
