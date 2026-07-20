<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent: production environments run migrations, not seeders,
        // so the new "staff" role (needed for staff login/attendance) has
        // to be inserted here rather than only in RoleSeeder.
        if (! DB::table('roles')->where('name', 'staff')->exists()) {
            DB::table('roles')->insert([
                'name' => 'staff',
                'display_name' => 'Staff',
                'description' => 'Non-teaching staff member with self-service access (e.g. attendance)',
                'permissions' => json_encode(['manage_own_attendance']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'staff')->delete();
    }
};
