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
        Schema::table('staff_members', function (Blueprint $table) {
            // Nullable: existing staff rows predate login accounts. New
            // staff get one created automatically (mirrors Teacher); older
            // ones are backfilled via a "generate login" action.
            $table->foreignId('user_id')->nullable()->after('school_id')
                ->constrained('users')->nullOnDelete();
            $table->string('plain_password')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'plain_password']);
        });
    }
};
