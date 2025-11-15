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
        // Create Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Get Super Admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        // Assign Super Admin role (no school required for super admin)
        UserSchoolRole::updateOrCreate(
            [
                'user_id' => $superAdmin->id,
                'role_id' => $superAdminRole->id,
                'school_id' => null
            ],
            [
                'status' => 'active'
            ]
        );

        $this->command->info('Super Admin created successfully!');
        $this->command->info('Email: admin@example.com');
        $this->command->info('Password: password');
    }
}
