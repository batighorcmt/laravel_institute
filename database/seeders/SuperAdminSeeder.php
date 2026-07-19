<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\UserSchoolRole;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin user only if it doesn't already exist.
        // Uses firstOrCreate (not updateOrCreate) so re-running this seeder never
        // resets a real admin's password/email back to these defaults.
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Get Super Admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        // Assign Super Admin role (no school required for super admin).
        // firstOrCreate here too, so an intentionally deactivated assignment
        // isn't silently re-activated by a re-run.
        UserSchoolRole::firstOrCreate(
            [
                'user_id' => $superAdmin->id,
                'role_id' => $superAdminRole->id,
                'school_id' => null
            ],
            [
                'status' => 'active'
            ]
        );

        $this->command->info('Super Admin ensured (existing credentials left untouched if already present).');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password (applies only on first creation)');
    }
}
